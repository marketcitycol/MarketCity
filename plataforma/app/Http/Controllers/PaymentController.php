<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\WebhookEvent;
use App\Services\Payments\OnlineGateway;
use App\Services\Payments\PaymentReconciler;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller
{
    private function gateway(PaymentRegistry $payments, string $key): OnlineGateway
    {
        $g = $payments->all()[$key] ?? null;
        abort_unless($g instanceof OnlineGateway, 404);

        return $g;
    }

    /** Aviso del proveedor: firma verificada, una sola vez por aviso, y el estado se consulta al proveedor. */
    public function webhook(Request $request, string $gateway, PaymentRegistry $payments, PaymentReconciler $reconciler)
    {
        $g = $this->gateway($payments, $gateway);
        if (! $g->verifyWebhook($request)) {
            return response()->json(['ok' => false], 401);
        }
        $id = $g->providerPaymentId($request);
        $key = $g->webhookEventKey($request);
        if (! $id || ! $key) {
            return response()->json(['ok' => true, 'ignorado' => true]);
        }

        try {
            $event = WebhookEvent::create(['provider' => $g->key(), 'event_key' => $key, 'signature_valid' => true, 'payload' => $request->all()]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(['ok' => true, 'repetido' => true]);
        }

        try {
            $result = $reconciler->apply($g, $id);
        } catch (Throwable $e) {
            report($e);
            $event->delete(); // se permite el reintento del proveedor

            return response()->json(['ok' => false], 503);
        }
        $event->update(['result' => $result, 'processed_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Regreso del navegador: solo muestra; el estado se consulta al proveedor, nunca se toma de la URL. */
    public function return(Request $request, string $gateway, Order $order, PaymentRegistry $payments, PaymentReconciler $reconciler)
    {
        abort_unless(hash_equals($order->access_token, (string) $request->query('t')), 404);
        $g = $this->gateway($payments, $gateway);
        if ($order->payment_status === 'pending' && ($id = $g->providerPaymentId($request))) {
            try {
                $reconciler->apply($g, $id, $order);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('orders.thanks', [$order, 't' => $order->access_token]);
    }
}
