<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\SellerOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correos de pedido que en WooCommerce estaban encendidos y v1 no enviaba:
 * - admin-new: nuevo pedido (equipo) · admin-failed: pago fallido o pedido vencido (equipo)
 * - payment-failed: el pago no se aprobó (cliente, con el plan B)
 * - cancelled: una tienda o el equipo canceló parte del pedido (cliente)
 * - delivered: pedido entregado (cliente, con retracto y garantía)
 * - note: nota del equipo al cliente
 */
class OrderNoticeMail extends Mailable
{
    public const KINDS = ['admin-new', 'admin-failed', 'payment-failed', 'cancelled', 'delivered', 'note'];

    public function __construct(public Order $order, public string $kind, public ?string $detail = null, public ?SellerOrder $sellerOrder = null) {}

    public function envelope(): Envelope
    {
        $n = $this->order->number;

        return new Envelope(subject: match ($this->kind) {
            'admin-new' => "Nuevo pedido {$n} · ".cop($this->order->total),
            'admin-failed' => "Pedido {$n} sin pago: cancelado",
            'payment-failed' => "No se aprobó el pago de tu pedido {$n}",
            'cancelled' => "Cancelamos parte de tu pedido {$n}",
            'delivered' => "Tu pedido {$n} fue entregado",
            default => "Novedad de tu pedido {$n}",
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-notice', with: [
            'order' => $this->order->loadMissing('sellerOrders.items', 'sellerOrders.store'),
            'kind' => $this->kind,
            'detail' => $this->detail,
            'so' => $this->sellerOrder?->loadMissing('items', 'store'),
        ]);
    }
}
