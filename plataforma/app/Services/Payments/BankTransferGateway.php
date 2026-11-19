<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;

/**
 * Transferencia bancaria: el pedido queda con pago pendiente y el comprador ve a dónde pagar.
 * Nunca se aprueba sola: el equipo registra el pago en el backoffice cuando el dinero llega.
 */
class BankTransferGateway implements PaymentGateway
{
    /** Datos de la cuenta que se configuran en Backoffice → Configuración. */
    public const FIELDS = [
        'transfer_bank' => 'Banco',
        'transfer_account_type' => 'Tipo de cuenta',
        'transfer_account_number' => 'Número de cuenta',
        'transfer_holder' => 'Titular',
        'transfer_holder_document' => 'Documento del titular',
    ];

    public function key(): string
    {
        return 'transferencia';
    }

    public function label(): string
    {
        return 'Transferencia bancaria';
    }

    public function description(): string
    {
        return 'Te mostramos los datos de la cuenta al confirmar. Despachamos cuando el pago se refleje.';
    }

    /** Solo se ofrece si están todos los datos de la cuenta: nunca un pedido sin saber a dónde pagar. */
    public static function isConfigured(): bool
    {
        foreach (array_keys(self::FIELDS) as $key) {
            if (trim((string) Setting::get($key, '')) === '') {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, string> etiqueta => valor */
    public static function accountDetails(): array
    {
        $out = [];
        foreach (self::FIELDS as $key => $label) {
            $out[$label] = (string) Setting::get($key, '');
        }

        return $out;
    }

    public static function deadlineHours(): int
    {
        return max(1, (int) Setting::get('transfer_deadline_hours', 24));
    }

    public function start(Order $order, Payment $payment): string
    {
        $payment->update(['provider_reference' => $order->number]);
        $order->log('pago', 'Pedido con transferencia bancaria: queda pendiente hasta confirmar que el dinero llegó.');

        return route('orders.thanks', [$order, 't' => $order->access_token]);
    }
}
