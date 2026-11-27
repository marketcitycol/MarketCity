<?php

namespace App\Services\Marketing;

use App\Models\MarketingConsent;
use App\Models\MarketingContact;
use App\Models\Setting;
use App\Support\ColombiaCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Permisos de mensajes por correo y WhatsApp (traslado del snippet #82).
 *
 * - Casillas opcionales por canal en el pago; nada viene marcado.
 * - Cada alta y cada baja queda en marketing_consents con fecha, texto exacto (huella y versión),
 *   origen, IP cifrada (HMAC) y navegador (Ley 1581 de 2012, Decreto 1377 de 2013).
 * - Horario legal (Ley 2300 de 2023): lunes a viernes 7:00-19:00, sábados 8:00-15:00, nunca domingos
 *   ni festivos; máximo un mensaje publicitario al día por persona y el mismo canal durante 7 días.
 * - Cupos de correos por hora y por día (cuida el límite del buzón de Hostinger) y de WhatsApp por día.
 * - Baja con un clic desde un enlace firmado; una baja solo de WhatsApp no da de baja el correo.
 */
class MessagingPermissions
{
    public const CHANNELS = ['email', 'whatsapp'];

    public const PURPOSES = ['carrito', 'ofertas'];

    public const TEXT_VERSION = '2026-11';

    public static function text(string $channel): string
    {
        return $channel === 'whatsapp'
            ? 'Acepto que MarketCity me escriba por WhatsApp para recordarme mi carrito si no termino la compra y enviarme ofertas, de lunes a viernes de 7 a. m. a 7 p. m. y sábados de 8 a. m. a 3 p. m., máximo una vez al día. Puedo retirarme cuando quiera.'
            : 'Acepto que MarketCity me escriba por correo para recordarme mi carrito si no termino la compra y enviarme ofertas, máximo una vez al día. Puedo retirarme cuando quiera.';
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Celular colombiano como 57XXXXXXXXXX; los que empiezan por 0 se rechazan. */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || str_starts_with($digits, '0')) {
            return null;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '57')) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^3\d{9}$/', $digits) ? '57'.$digits : null;
    }

    /** Contacto que corresponde al canal: correo para email, celular para WhatsApp. */
    public static function contactFor(string $channel, ?string $email, ?string $phone): ?string
    {
        return $channel === 'whatsapp' ? self::normalizePhone($phone) : self::normalizeEmail($email);
    }

    /**
     * Registra lo que el cliente dejó marcado o desmarcado en cada canal.
     *
     * @param  array<string,bool>  $channels  ['email' => true, 'whatsapp' => false]
     * @param  array<string,mixed>  $meta  source, ip, user_agent, order_id, user_id
     */
    public function record(?string $email, ?string $phone, array $channels, array $meta = []): void
    {
        foreach ($channels as $channel => $granted) {
            if (! in_array($channel, self::CHANNELS, true)) {
                continue;
            }
            $contact = self::contactFor($channel, $email, $phone);
            if (! $contact) {
                continue;
            }
            // Desde el pago, desmarcar solo retira lo que se autorizó en esta misma visita: escribir el
            // correo de otra persona con la casilla vacía no le quita sus permisos.
            if (! $granted && ! empty($meta['session_only']) && ! in_array($channel.':'.$contact, (array) session('messages.granted', []), true)) {
                continue;
            }
            if ($granted && ! empty($meta['session_only'])) {
                session()->push('messages.granted', $channel.':'.$contact);
            }
            foreach (self::PURPOSES as $purpose) {
                // Solo se guarda el cambio: marcar dos veces no llena el registro.
                if ($this->allows($contact, $channel, $purpose) === (bool) $granted) {
                    continue;
                }
                MarketingConsent::create([
                    'contact' => $contact,
                    'channel' => $channel,
                    'purpose' => $purpose,
                    'granted' => (bool) $granted,
                    'source' => $meta['source'] ?? 'checkout',
                    'text_version' => $granted ? self::TEXT_VERSION : null,
                    'text_hash' => $granted ? hash('sha256', self::text($channel)) : null,
                    'ip_hash' => isset($meta['ip']) ? hash_hmac('sha256', (string) $meta['ip'], (string) config('app.key')) : null,
                    'user_agent' => isset($meta['user_agent']) ? mb_substr((string) $meta['user_agent'], 0, 255) : null,
                    'order_id' => $meta['order_id'] ?? null,
                    'user_id' => $meta['user_id'] ?? null,
                ]);
            }
        }
    }

    public function allows(?string $contact, string $channel, string $purpose = 'carrito'): bool
    {
        if (! $contact) {
            return false;
        }
        $last = MarketingConsent::where('contact', $contact)->where('channel', $channel)->where('purpose', $purpose)->latest('id')->first();

        return (bool) $last?->granted;
    }

    /** Baja de un contacto en un canal o en todos. */
    public function revoke(?string $email, ?string $phone, string $channel = 'todo', string $source = 'baja'): void
    {
        $channels = $channel === 'todo' ? self::CHANNELS : [$channel];
        $this->record($email, $phone, array_fill_keys($channels, false), ['source' => $source]);
    }

    public function unsubscribeUrl(?string $email, ?string $phone): string
    {
        $payload = Crypt::encryptString(json_encode(['e' => self::normalizeEmail($email), 'p' => self::normalizePhone($phone)]));

        return URL::signedRoute('marketing.unsubscribe', ['d' => $payload]);
    }

    /** @return array{e:?string,p:?string}|null */
    public static function decodeUnsubscribe(string $payload): ?array
    {
        try {
            $data = json_decode(Crypt::decryptString($payload), true);
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? ['e' => $data['e'] ?? null, 'p' => $data['p'] ?? null] : null;
    }

    public static function inLegalHours(?CarbonInterface $at = null): bool
    {
        $at = CarbonImmutable::instance($at ?? now())->setTimezone(ColombiaCalendar::TZ);
        if ($at->isSunday() || isset(ColombiaCalendar::holidays($at->year)[$at->format('Y-m-d')])) {
            return false;
        }
        $minutes = $at->hour * 60 + $at->minute;

        return $at->isSaturday() ? $minutes >= 8 * 60 && $minutes < 15 * 60 : $minutes >= 7 * 60 && $minutes < 19 * 60;
    }

    /** Próximo momento dentro del horario legal (para reprogramar un recordatorio). */
    public static function nextLegalTime(?CarbonInterface $from = null): CarbonImmutable
    {
        $at = CarbonImmutable::instance($from ?? now())->setTimezone(ColombiaCalendar::TZ)->second(0);
        for ($i = 0; $i < 24 * 8 && ! self::inLegalHours($at); $i++) {
            $at = $at->addMinutes(30)->minute($at->minute < 30 ? 30 : 0);
        }

        return $at->utc();
    }

    /** Motivo por el que hoy no se le puede escribir (vacío = se puede). */
    public function whyNot(?string $email, ?string $phone, string $channel, ?CarbonInterface $at = null): string
    {
        $at ??= now();
        $email = self::normalizeEmail($email);
        $phone = self::normalizePhone($phone);
        if (! self::contactFor($channel, $email, $phone)) {
            return 'sin-contacto';
        }
        if (! self::inLegalHours($at)) {
            return 'fuera-horario';
        }
        $mine = fn () => MarketingContact::where(fn ($q) => $q->when($email, fn ($q) => $q->orWhere('email', $email))->when($phone, fn ($q) => $q->orWhere('phone', $phone)));
        $dayStart = CarbonImmutable::instance($at)->setTimezone(ColombiaCalendar::TZ)->startOfDay()->utc();
        if ($mine()->where('sent_at', '>=', $dayStart)->exists()) {
            return 'hoy';
        }
        if ($mine()->where('sent_at', '>=', CarbonImmutable::instance($at)->subDays(7))->where('channel', '!=', $channel)->exists()) {
            return 'otro-canal';
        }
        if ($channel === 'email') {
            if (MarketingContact::where('channel', 'email')->where('sent_at', '>=', CarbonImmutable::instance($at)->subHour())->count() >= (int) Setting::get('marketing_email_hour_cap', 40)) {
                return 'cupo-hora';
            }
            if (MarketingContact::where('channel', 'email')->where('sent_at', '>=', $dayStart)->count() >= (int) Setting::get('marketing_email_day_cap', 300)) {
                return 'cupo-dia';
            }
        } elseif (MarketingContact::where('channel', 'whatsapp')->where('sent_at', '>=', $dayStart)->count() >= (int) Setting::get('marketing_whatsapp_day_cap', 100)) {
            return 'cupo-dia';
        }

        return '';
    }

    public function logContact(?string $email, ?string $phone, string $channel, string $source, ?string $reference = null): void
    {
        MarketingContact::create([
            'email' => self::normalizeEmail($email),
            'phone' => self::normalizePhone($phone),
            'channel' => $channel,
            'source' => $source,
            'reference' => $reference,
            'sent_at' => now(),
        ]);
    }
}
