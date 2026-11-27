<?php

namespace App\Services\Marketing;

use App\Mail\CartReminderMail;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Orders\Cart;
use App\Support\Mailer;
use Illuminate\Support\Str;

/**
 * Recuperar carritos abandonados (traslado del snippet #83).
 *
 * Solo para quien marcó Correo o WhatsApp en el pago (MessagingPermissions). Guarda su carrito y,
 * si no compra, le envía hasta 2 recordatorios (3 si se activa): a la hora y a las 24 horas, por un
 * solo canal. El enlace rehace el carrito en cualquier dispositivo. Se detiene solo si compra, se da de
 * baja, vacía el carrito o los productos se agotan. Respeta el horario legal y los cupos.
 *
 * Modos (Backoffice → Mensajes): off (no guarda ni envía), test (solo envía a los correos de prueba y a
 * administradores; al resto lo simula) y public. WhatsApp no se envía solo: el panel arma el mensaje
 * y el equipo lo abre en WhatsApp después de revisar permisos y horario.
 */
class AbandonedCarts
{
    public const DELAYS_HOURS = [1, 24, 72];

    public function __construct(private MessagingPermissions $permissions, private Cart $cart) {}

    public static function mode(): string
    {
        $mode = (string) Setting::get('abandoned_cart_mode', 'off');

        return in_array($mode, ['off', 'test', 'public'], true) ? $mode : 'off';
    }

    public static function maxReminders(): int
    {
        return Setting::get('abandoned_cart_third') ? 3 : 2;
    }

    /** El cliente marcó o desmarcó casillas en el pago: se guarda, actualiza o borra la copia del carrito. */
    public function capture(?string $email, ?string $phone, ?string $name, array $channels, string $sessionId, ?int $userId): ?AbandonedCart
    {
        if (self::mode() === 'off') {
            return null;
        }
        $row = $this->currentRow($sessionId);
        $channel = ! empty($channels['email']) && MessagingPermissions::normalizeEmail($email) ? 'email'
            : (! empty($channels['whatsapp']) && MessagingPermissions::normalizePhone($phone) ? 'whatsapp' : null);

        if (! $channel) {
            // Desmarcó antes de comprar y no se le ha escrito: se borra lo guardado.
            if ($row && $row->reminders_sent === 0) {
                $row->delete();
            }

            return null;
        }
        if ($this->cart->isEmpty()) {
            return $row;
        }

        $data = [
            'email' => MessagingPermissions::normalizeEmail($email),
            'phone' => MessagingPermissions::normalizePhone($phone),
            'name' => $name ? mb_substr(preg_replace("/[^\\p{L} '\\-]/u", '', $name), 0, 80) : null,
            'channel' => $channel,
            'items' => $this->cart->raw(),
            'total' => $this->cart->subtotal(),
            'user_id' => $userId,
        ];

        if ($row) {
            $row->update($data);

            return $row;
        }

        $row = AbandonedCart::create($data + [
            'token' => Str::random(48),
            'session_id' => $sessionId,
            'next_reminder_at' => now()->addHours(self::DELAYS_HOURS[0]),
        ]);
        session(['messages.cart_id' => $row->id]);

        return $row;
    }

    /** Copia abierta de este visitante: la que guardó su sesión (o, si no, la de su id de sesión). */
    private function currentRow(string $sessionId): ?AbandonedCart
    {
        $id = session('messages.cart_id');
        $row = $id ? AbandonedCart::where('id', $id)->where('status', 'open')->first() : null;

        return $row ?? AbandonedCart::where('session_id', $sessionId)->where('status', 'open')->latest('id')->first();
    }

    /** El carrito cambió: la copia abierta de esa sesión se actualiza (o se detiene si quedó vacío). */
    public function refresh(string $sessionId): void
    {
        $row = $this->currentRow($sessionId);
        if (! $row || $row->order_id) {
            return;
        }
        if ($this->cart->isEmpty()) {
            $row->update(['status' => 'stopped', 'stop_reason' => 'vacio', 'next_reminder_at' => null]);

            return;
        }
        $row->update(['items' => $this->cart->raw(), 'total' => $this->cart->subtotal()]);
    }

    /** Se creó un pedido: queda enlazado; si se paga, el carrito cuenta como recuperado. */
    public function attachOrder(string $sessionId, Order $order): void
    {
        AbandonedCart::where('status', 'open')->where(fn ($q) => $q->where('id', session('messages.cart_id'))->orWhere('session_id', $sessionId)->orWhere('email', MessagingPermissions::normalizeEmail($order->email)))
            ->where('created_at', '<=', $order->created_at)
            ->update(['order_id' => $order->id]);
    }

    /** Recorre las copias con recordatorio vencido. Devuelve un resumen para el log del programador. */
    public function tick(): array
    {
        $out = ['enviados' => 0, 'simulados' => 0, 'detenidos' => 0, 'reprogramados' => 0, 'whatsapp_manual' => 0];
        if (self::mode() === 'off') {
            return $out;
        }

        AbandonedCart::where('status', 'open')->whereNotNull('next_reminder_at')->where('next_reminder_at', '<=', now())
            ->orderBy('next_reminder_at')->limit(100)->get()
            ->each(function (AbandonedCart $row) use (&$out) {
                $out[$this->process($row)]++;
            });

        return $out;
    }

    private function process(AbandonedCart $row): string
    {
        $order = $row->order_id ? Order::find($row->order_id) : null;
        $paidLater = Order::where('payment_status', 'paid')->where('created_at', '>=', $row->created_at)
            ->where(fn ($q) => $q->where('email', $row->email)->when($row->user_id, fn ($q) => $q->orWhere('user_id', $row->user_id)))->exists();
        if (($order && $order->payment_status === 'paid') || $paidLater) {
            $row->update(['status' => 'recovered', 'stop_reason' => 'compro', 'next_reminder_at' => null]);

            return 'detenidos';
        }

        $contact = MessagingPermissions::contactFor($row->channel, $row->email, $row->phone);
        if (! $this->permissions->allows($contact, $row->channel, 'carrito')) {
            return $this->stop($row, 'baja');
        }
        $lines = $this->lines($row);
        if (! $lines) {
            return $this->stop($row, 'agotado');
        }

        $why = $this->permissions->whyNot($row->email, $row->phone, $row->channel);
        if ($why === 'otro-canal') {
            return $this->stop($row, 'otro-canal');
        }
        if ($why !== '') {
            $next = $why === 'fuera-horario' ? MessagingPermissions::nextLegalTime() : MessagingPermissions::nextLegalTime(now()->addHour());
            $row->update(['next_reminder_at' => $next]);

            return 'reprogramados';
        }

        if ($row->channel === 'whatsapp') {
            // Sin API de WhatsApp Business: queda en el panel para envío manual (no se reprograma).
            $row->update(['next_reminder_at' => null]);

            return 'whatsapp_manual';
        }

        $unpaid = $order && $order->payment_status === 'pending';
        $isTestRecipient = in_array($row->email, $this->testEmails(), true) || User::where('email', $row->email)->where('role', 'admin')->exists();
        if (self::mode() === 'test' && ! $isTestRecipient) {
            $this->advance($row);

            return 'simulados';
        }

        Mailer::send($row->email, new CartReminderMail($row, $lines, $this->restoreUrl($row), $this->permissions->unsubscribeUrl($row->email, $row->phone), $unpaid));
        $this->permissions->logContact($row->email, $row->phone, 'email', 'carrito', (string) $row->id);
        $this->advance($row, true);

        return 'enviados';
    }

    /** Marca el recordatorio como hecho y programa el siguiente (o termina la secuencia). */
    public function advance(AbandonedCart $row, bool $sent = false): void
    {
        $n = $row->reminders_sent + 1;
        $next = $n < self::maxReminders() ? $row->created_at->copy()->addHours(self::DELAYS_HOURS[$n]) : null;
        $row->update([
            'reminders_sent' => $n,
            'last_sent_at' => $sent ? now() : $row->last_sent_at,
            'next_reminder_at' => $next && $next->isPast() ? now()->addHour() : $next,
            'status' => $next ? 'open' : 'done',
        ]);
    }

    private function stop(AbandonedCart $row, string $reason): string
    {
        $row->update(['status' => 'stopped', 'stop_reason' => $reason, 'next_reminder_at' => null]);

        return 'detenidos';
    }

    /** @return array<int, array{name:string, quantity:int, price:int}> */
    public function lines(AbandonedCart $row): array
    {
        $items = (array) $row->items;

        return Product::visible()->whereIn('id', array_keys($items))->where('stock', '>', 0)->get()
            ->map(fn (Product $p) => ['name' => $p->name, 'quantity' => min((int) $items[$p->id], $p->stock), 'price' => (int) $p->price])
            ->values()->all();
    }

    public function restoreUrl(AbandonedCart $row): string
    {
        return route('cart.recover', $row->token);
    }

    /** Mensaje para abrir WhatsApp desde el panel (sin datos personales más allá del nombre). */
    public function whatsappLink(AbandonedCart $row): ?string
    {
        if (! $row->phone) {
            return null;
        }
        $text = 'Hola'.($row->name ? ' '.$row->name : '').', te escribimos de MarketCity. Dejaste productos en tu carrito: '.$this->restoreUrl($row).' (si no quieres más mensajes, responde NO).';

        return 'https://wa.me/'.$row->phone.'?text='.rawurlencode($text);
    }

    /** @return array<int,string> */
    private function testEmails(): array
    {
        return array_values(array_filter(array_map(fn ($e) => MessagingPermissions::normalizeEmail($e), preg_split('/[\s,;]+/', (string) Setting::get('abandoned_cart_test_emails', '')))));
    }

    /** Ley 1581: las copias de más de 30 días se borran. */
    public function purge(): int
    {
        return AbandonedCart::where('updated_at', '<', now()->subDays(30))->delete();
    }
}
