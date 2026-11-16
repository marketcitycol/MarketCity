<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * Contrato para cualquier medio de pago (Bold, Wompi, Mercado Pago, Addi, Sistecrédito, MarketCityPay...).
 * Cada adaptador recibe las credenciales del titular vigente (MerchantEntity), nunca las tiene fijas en el código.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /** Crea el intento de pago y devuelve la URL a la que se envía al comprador. */
    public function start(Order $order, Payment $payment): string;
}
