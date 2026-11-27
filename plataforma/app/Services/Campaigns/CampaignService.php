<?php

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\ColombiaCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Campañas con precio programado y tope de unidades (traslado de los snippets #16, #77 y #78).
 *
 * Igual que las ofertas programadas de WooCommerce, el precio de campaña se escribe en el producto
 * mientras la campaña corre (así el catálogo, los filtros, el carrito y el feed lo usan sin cambios)
 * y el precio normal queda guardado en campaign_products.base_price para devolverlo al terminar.
 *
 * Tope (#77): cuenta las unidades vendidas a precio de campaña en pedidos vigentes (pagados o
 * pendientes de pago; no cancelados). Al llegar al tope el producto vuelve a su precio normal; si un
 * pedido se cancela y vuelven a quedar unidades antes del fin, recupera el precio de campaña.
 * No toca el inventario real.
 */
class CampaignService
{
    /** Aplica o retira precios según fechas y topes. Idempotente: corre cada minuto y tras cada pedido. */
    public function sync(?Product $only = null): int
    {
        $changed = 0;
        $query = CampaignProduct::with('campaign', 'product');
        if ($only) {
            $query->where('product_id', $only->id);
        }
        foreach ($query->get() as $item) {
            $changed += $this->syncItem($item) ? 1 : 0;
        }

        return $changed;
    }

    public function syncItem(CampaignProduct $item): bool
    {
        return DB::transaction(function () use ($item) {
            $item = CampaignProduct::with('campaign')->lockForUpdate()->find($item->id);
            $product = $item ? Product::lockForUpdate()->find($item->product_id) : null;
            if (! $item || ! $product) {
                return false;
            }
            $campaign = $item->campaign;
            $running = $campaign->isRunning();

            if ($running && $campaign->unit_cap) {
                $sold = $this->soldUnits($item);
                if (! $item->closed_at && $sold >= $campaign->unit_cap) {
                    $item->closed_at = now();
                } elseif ($item->closed_at && $sold < $campaign->unit_cap) {
                    $item->closed_at = null;
                }
            }

            $shouldApply = $running && ! $item->closed_at && $item->campaign_price < ($item->base_price ?? $product->price);
            $changed = false;

            if ($shouldApply && ! $item->applied_at) {
                // Otra campaña ya aplicada al mismo producto tiene prioridad: no se encadenan precios.
                $busy = CampaignProduct::where('product_id', $product->id)->where('id', '!=', $item->id)->whereNotNull('applied_at')->exists();
                if (! $busy) {
                    $item->fill(['base_price' => $product->price, 'base_regular_price' => $product->regular_price, 'applied_at' => now()]);
                    $product->update(['price' => $item->campaign_price, 'regular_price' => max((int) $product->regular_price, (int) $product->price)]);
                    $changed = true;
                }
            } elseif (! $shouldApply && $item->applied_at) {
                $product->update(['price' => $item->base_price, 'regular_price' => $item->base_regular_price]);
                $item->fill(['applied_at' => null]);
                $changed = true;
            } elseif ($shouldApply && $item->applied_at && $product->price !== $item->campaign_price) {
                $product->update(['price' => $item->campaign_price]);
                $changed = true;
            }

            $item->save();

            return $changed;
        });
    }

    /** Unidades vendidas a precio de campaña en pedidos que siguen vigentes. */
    public function soldUnits(CampaignProduct $item): int
    {
        return (int) OrderItem::where('campaign_id', $item->campaign_id)->where('product_id', $item->product_id)
            ->whereHas('order', fn ($o) => $o->whereIn('payment_status', ['pending', 'paid']))
            ->whereHas('sellerOrder', fn ($s) => $s->where('status', '!=', 'cancelado'))
            ->sum('quantity');
    }

    /** Campaña aplicada hoy al producto (con su campaña cargada) o null. */
    public function activeFor(Product $product): ?CampaignProduct
    {
        return CampaignProduct::with('campaign')->where('product_id', $product->id)->whereNotNull('applied_at')->first();
    }

    /** Unidades que quedan a precio de campaña, o null si no hay tope. */
    public function remaining(CampaignProduct $item): ?int
    {
        $cap = $item->campaign->unit_cap;

        return $cap ? max(0, $cap - $this->soldUnits($item)) : null;
    }

    /** Cantidad máxima que se puede llevar a precio de campaña (o null si no aplica tope). */
    public function maxQuantity(Product $product): ?int
    {
        $item = $this->activeFor($product);

        return $item ? $this->remaining($item) : null;
    }

    /**
     * Unidades de una oferta que ya compró un cliente (por correo o por su cuenta) en pedidos vigentes.
     * Sirve para el límite por cliente de las ofertas relámpago.
     */
    public function boughtBy(CampaignProduct $item, ?string $email, ?int $userId = null): int
    {
        $email = $email ? mb_strtolower(trim($email)) : null;
        if (! $email && ! $userId) {
            return 0;
        }

        return (int) OrderItem::where('campaign_id', $item->campaign_id)->where('product_id', $item->product_id)
            ->whereHas('order', fn ($o) => $o->whereIn('payment_status', ['pending', 'paid'])
                ->where(fn ($w) => $w->when($email, fn ($q) => $q->whereRaw('LOWER(email) = ?', [$email]))
                    ->when($userId, fn ($q) => $q->orWhere('user_id', $userId))))
            ->whereHas('sellerOrder', fn ($s) => $s->where('status', '!=', 'cancelado'))
            ->sum('quantity');
    }

    /** Unidades que todavía puede llevar este cliente a precio de oferta, o null si la oferta no tiene límite. */
    public function customerAllowance(Product $product, ?User $user = null, ?string $email = null): ?int
    {
        $item = $this->activeFor($product);
        $limit = $item?->campaign->per_customer_limit;
        if (! $limit) {
            return null;
        }

        return max(0, $limit - $this->boughtBy($item, $email ?? $user?->email, $user?->id));
    }

    /** Cuando un vendedor cambia el precio durante la campaña, el cambio queda como precio normal. */
    public function protectBasePrice(Product $product, array $data): array
    {
        $item = $this->activeFor($product);
        if (! $item || ! isset($data['price'])) {
            return $data;
        }
        $item->update(['base_price' => (int) $data['price'], 'base_regular_price' => $data['regular_price'] ?? null]);
        unset($data['price'], $data['regular_price']);

        return $data;
    }

    /** Productos de una campaña para su página (cuadrícula, #78). */
    public function products(Campaign $campaign)
    {
        return Product::visible()->with('store', 'brand', 'category')
            ->whereIn('id', $campaign->items()->whereNotNull('applied_at')->pluck('product_id'))
            ->orderByDesc('sales_count')->get();
    }

    /** Hora de corte de despacho de hoy (#16), solo si el Backoffice la configuró. */
    public static function dispatchCutoff(): ?CarbonImmutable
    {
        $hour = Setting::get('dispatch_cutoff_hour');
        if ($hour === null || $hour === '') {
            return null;
        }
        $now = now()->toImmutable()->setTimezone(ColombiaCalendar::TZ);
        $saturdayHour = Setting::get('dispatch_cutoff_saturday_hour');
        if ($now->isSunday() || isset(ColombiaCalendar::holidays($now->year)[$now->format('Y-m-d')])) {
            return null;
        }
        $h = $now->isSaturday() ? $saturdayHour : $hour;
        if ($h === null || $h === '') {
            return null;
        }
        $cut = $now->setTime((int) $h, 0);

        return $now->lt($cut) ? $cut : null;
    }
}
