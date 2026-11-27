<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\EmailPriceService;
use App\Services\Orders\Cart;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /** Página de la campaña con todos sus productos en cuadrícula (#78). */
    public function show(Campaign $campaign, CampaignService $campaigns)
    {
        abort_unless($campaign->active, 404);

        return view('shop.campaign', [
            'campaign' => $campaign,
            'products' => $campaign->isRunning() ? $campaigns->products($campaign) : collect(),
        ]);
    }

    /** «Ver mi precio»: deja el correo y recibe un cupón único (#76). */
    public function emailPrice(Request $request, Product $product, EmailPriceService $prices, Cart $cart)
    {
        $data = $request->validate([
            'email' => 'required|email:rfc|max:120',
            'consent' => 'accepted',
            'website' => 'nullable|max:0', // campo trampa para robots
        ], ['consent.accepted' => 'Para darte el precio necesitamos tu autorización de datos.']);

        $coupon = $prices->unlock($product, $data['email'], $request->ip());
        if (! $coupon) {
            return back()->with('error', 'Este producto ya no tiene precio con correo.');
        }
        if (! $coupon->isUsable()) {
            return back()->with('error', 'Ya usaste tu precio con correo para este producto.');
        }
        $error = $cart->applyCoupon($coupon->code);

        return $error
            ? back()->with('error', $error)
            : redirect()->route('cart')->with('success', "Listo: tu precio quedó en el carrito (código {$coupon->code}, también te lo enviamos al correo). Paga con ese mismo correo.");
    }
}
