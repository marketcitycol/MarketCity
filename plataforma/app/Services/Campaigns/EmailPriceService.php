<?php

namespace App\Services\Campaigns;

use App\Mail\EmailPriceMail;
use App\Models\CampaignProduct;
use App\Models\EmailPriceCoupon;
use App\Models\Product;
use App\Support\Mailer;
use Illuminate\Support\Str;

/**
 * Precio con correo (traslado del snippet #76 «Black Friday: precio con correo»).
 *
 * El cliente deja su correo y su autorización de datos en la ficha y recibe un cupón único:
 * 1 uso, 1 unidad, solo ese producto, ligado a su correo y con vencimiento al final de la campaña.
 * Se paga el menor entre el precio prometido al pedir el cupón y el precio con correo del momento.
 * Si el producto sale de la campaña por el tope de unidades, el cupón deja de servir.
 */
class EmailPriceService
{
    public const CONSENT_TEXT = 'Autorizo a MarketCity a usar mi correo para enviarme este cupón y validar que lo use yo (Ley 1581 de 2012). Se borra 30 días después de la campaña.';

    public function __construct(private CampaignService $campaigns) {}

    /** Oferta de precio con correo vigente para el producto, o null. */
    public function offerFor(Product $product): ?CampaignProduct
    {
        $item = $this->campaigns->activeFor($product);

        return $item && $item->campaign->email_price_enabled && $item->email_price && $item->email_price < $product->price ? $item : null;
    }

    public function unlock(Product $product, string $email, ?string $ip): ?EmailPriceCoupon
    {
        $offer = $this->offerFor($product);
        if (! $offer) {
            return null;
        }
        $hash = EmailPriceCoupon::hashEmail($email);
        $coupon = EmailPriceCoupon::firstOrCreate(
            ['campaign_id' => $offer->campaign_id, 'product_id' => $product->id, 'email_hash' => $hash],
            [
                'email' => mb_strtolower(trim($email)),
                'code' => $this->newCode(),
                'promised_price' => $offer->email_price,
                'expires_at' => $offer->campaign->ends_at,
                'consent_hash' => hash('sha256', self::CONSENT_TEXT),
                'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
            ],
        );
        if ($coupon->wasRecentlyCreated) {
            Mailer::send($coupon->email, new EmailPriceMail($coupon->load('product', 'campaign')));
        }

        return $coupon;
    }

    /** Cupón válido para este producto (sin mirar el correo del comprador, que se revisa al pagar). */
    public function validFor(string $code, Product $product): ?EmailPriceCoupon
    {
        $coupon = EmailPriceCoupon::where('code', strtoupper(trim($code)))->where('product_id', $product->id)->first();
        if (! $coupon || ! $coupon->isUsable()) {
            return null;
        }
        $item = $this->campaigns->activeFor($product);
        // Sin campaña aplicada (terminó o se llenó el tope) el cupón ya no sirve.
        if (! $item || $item->campaign_id !== $coupon->campaign_id) {
            return null;
        }

        return $coupon;
    }

    /** Precio de la unidad con cupón: el menor entre lo prometido y el precio con correo de hoy. */
    public function unitPrice(EmailPriceCoupon $coupon, Product $product): int
    {
        $offer = $this->offerFor($product);
        $today = $offer?->email_price ?? $coupon->promised_price;

        return min($coupon->promised_price, $today, $product->price);
    }

    /** Si el pago se rechaza o el pedido se cancela, el cupón vuelve a servir mientras no venza. */
    public function release(int $orderId): void
    {
        EmailPriceCoupon::where('order_id', $orderId)->update(['used_at' => null, 'order_id' => null]);
    }

    /** Ley 1581: 30 días después del vencimiento se borra el correo y queda solo la huella. */
    public function purge(): int
    {
        return EmailPriceCoupon::whereNotNull('email')->where('expires_at', '<', now()->subDays(30))->update(['email' => null]);
    }

    private function newCode(): string
    {
        do {
            $code = 'MC'.strtoupper(Str::random(8));
        } while (EmailPriceCoupon::where('code', $code)->exists());

        return $code;
    }
}
