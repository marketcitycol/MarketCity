<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Campaigns\CampaignService;
use App\Support\ColombiaCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Backoffice → Campañas: precio programado, tope de unidades y precio con correo (#16, #76, #77, #78). */
class CampaignController extends Controller
{
    public function index()
    {
        return view('admin.campaigns.index', [
            'campaigns' => Campaign::withCount('items')->latest('starts_at')->get(),
            'cutoff' => Setting::get('dispatch_cutoff_hour'),
            'cutoffSaturday' => Setting::get('dispatch_cutoff_saturday_hour'),
        ]);
    }

    /** Hora de corte de despacho (#16): «Pide antes de las X y lo despachamos hoy». Vacío = no se muestra. */
    public function cutoff(Request $request)
    {
        $data = $request->validate(['hour' => 'nullable|integer|min:6|max:22', 'saturday_hour' => 'nullable|integer|min:6|max:22']);
        Setting::put('dispatch_cutoff_hour', $data['hour'] ?? '');
        Setting::put('dispatch_cutoff_saturday_hour', $data['saturday_hour'] ?? '');

        return back()->with('success', 'Hora de corte guardada.');
    }

    public function create()
    {
        return view('admin.campaigns.form', ['campaign' => new Campaign(['unit_cap' => 10, 'active' => true]), 'items' => collect(), 'campaigns' => app(CampaignService::class)]);
    }

    public function store(Request $request, CampaignService $campaigns)
    {
        $campaign = Campaign::create($this->validated($request) + ['slug' => $this->slug($request->input('slug') ?: $request->input('name'))]);
        $campaigns->sync();

        return redirect()->route('admin.campaigns.edit', $campaign)->with('success', 'Campaña creada. Ahora agrega sus productos.');
    }

    public function edit(Campaign $campaign, CampaignService $campaigns)
    {
        return view('admin.campaigns.form', [
            'campaign' => $campaign,
            'items' => $campaign->items()->with('product.store')->get(),
            'campaigns' => $campaigns,
        ]);
    }

    public function update(Request $request, Campaign $campaign, CampaignService $campaigns)
    {
        $campaign->update($this->validated($request));
        $campaigns->sync();

        return back()->with('success', 'Campaña guardada. Los precios ya se aplicaron según las fechas.');
    }

    /** Agrega o actualiza un producto (por SKU o id) con su precio de campaña y, opcional, su precio con correo. */
    public function addProduct(Request $request, Campaign $campaign, CampaignService $campaigns)
    {
        $data = $request->validate([
            'product' => 'required|string|max:64',
            'campaign_price' => 'required|integer|min:'.Product::MIN_PRICE,
            'email_price' => 'nullable|integer|min:'.Product::MIN_PRICE.'|lt:campaign_price',
        ], ['email_price.lt' => 'El precio con correo debe ser menor que el precio de campaña.']);
        $product = Product::where('sku', $data['product'])->orWhere('id', ctype_digit($data['product']) ? (int) $data['product'] : 0)->first();
        if (! $product) {
            return back()->withInput()->with('error', 'No encontramos un producto con ese SKU o número.');
        }
        $item = CampaignProduct::firstOrNew(['campaign_id' => $campaign->id, 'product_id' => $product->id]);
        $normal = $item->applied_at ? $item->base_price : $product->price;
        if ($data['campaign_price'] >= $normal) {
            return back()->withInput()->with('error', 'El precio de campaña debe ser menor que el precio normal ('.cop($normal).').');
        }
        $item->fill(['campaign_price' => $data['campaign_price'], 'email_price' => $data['email_price'] ?? null])->save();
        $campaigns->syncItem($item);

        return back()->with('success', "«{$product->name}» quedó en la campaña.");
    }

    public function removeProduct(Campaign $campaign, CampaignProduct $item, CampaignService $campaigns)
    {
        abort_unless($item->campaign_id === $campaign->id, 404);
        // Primero se devuelve el precio normal y luego se quita de la campaña.
        if ($item->applied_at) {
            $item->product?->update(['price' => $item->base_price, 'regular_price' => $item->base_regular_price]);
        }
        $item->delete();

        return back()->with('success', 'Producto quitado de la campaña; volvió a su precio normal.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'badge' => 'nullable|string|max:30',
            'starts_at' => 'required|date_format:Y-m-d\TH:i',
            'ends_at' => 'required|date_format:Y-m-d\TH:i|after:starts_at',
            'unit_cap' => 'nullable|integer|min:1|max:100000',
            'active' => 'nullable|boolean',
            'email_price_enabled' => 'nullable|boolean',
        ]);
        // Las fechas se escriben en hora de Colombia y se guardan en UTC.
        foreach (['starts_at', 'ends_at'] as $k) {
            $data[$k] = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data[$k], ColombiaCalendar::TZ)->utc();
        }
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['email_price_enabled'] = (bool) ($data['email_price_enabled'] ?? false);

        return $data;
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'campana';
        $slug = $base;
        for ($i = 2; Campaign::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
