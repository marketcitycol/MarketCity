<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

class CashOnDeliveryGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'contraentrega';
    }

    public function label(): string
    {
        return 'Pago contraentrega';
    }

    public function description(): string
    {
        return 'Pagas en efectivo cuando recibes el pedido.';
    }

    public function start(Order $order, Payment $payment): string
    {
        $order->log('pago', 'Pedido con pago contraentrega: se cobra al entregar.');

        return route('orders.thanks', [$order, 't' => $order->access_token]);
    }
}
