<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Payments\PaymentRegistry;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_unless($page->isPublished(), 404);

        return view('shop.page', ['page' => $page]);
    }

    /** «Compra ahora, paga después» con simulador de cuotas (#59). */
    public function bnpl(PaymentRegistry $payments)
    {
        $enabled = collect($payments->enabled())->except('sandbox');

        return view('shop.bnpl', [
            'creditLive' => $enabled->has('addi') || $enabled->has('sistecredito'),
            'methods' => $enabled->map->label()->values()->join(', ', ' o '),
        ]);
    }
}
