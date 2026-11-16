<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/** Pasarela de prueba: deja aprobar o rechazar el pago sin dinero real. */
class SandboxGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'sandbox';
    }

    public function label(): string
    {
        return 'Pago de prueba';
    }

    public function description(): string
    {
        return 'Simula una pasarela: puedes aprobar o rechazar el pago. No mueve dinero.';
    }

    public function start(Order $order, Payment $payment): string
    {
        $payment->update(['provider_reference' => 'SBX-'.strtoupper(bin2hex(random_bytes(5)))]);

        return route('payments.sandbox', [$order, 't' => $order->access_token]);
    }
}
