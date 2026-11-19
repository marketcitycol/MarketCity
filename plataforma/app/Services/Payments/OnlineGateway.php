<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Pasarela en línea: además de iniciar el pago, valida los avisos firmados del proveedor y consulta
 * el estado real servidor a servidor. Un pago nunca se aprueba por el regreso del navegador.
 */
interface OnlineGateway extends PaymentGateway
{
    /** Hay llaves cargadas en el servidor. Sin llaves el medio no se ofrece. */
    public static function isConfigured(): bool;

    /** Verifica la firma del aviso. */
    public function verifyWebhook(Request $request): bool;

    /** Identificador único del aviso, para no procesarlo dos veces. */
    public function webhookEventKey(Request $request): ?string;

    /** Referencia del pago en el proveedor que trae el aviso o el regreso del navegador. */
    public function providerPaymentId(Request $request): ?string;

    /**
     * Consulta el pago al proveedor y devuelve su estado normalizado.
     *
     * @return array{status: 'approved'|'declined'|'pending', order_number: ?string, amount: ?int, currency: ?string, raw: array}
     */
    public function fetchPayment(string $providerPaymentId): array;

    /** Busca en el proveedor el último pago hecho para un pedido (conciliación si no llegó el aviso). */
    public function findPaymentForOrder(Order $order): ?string;
}
