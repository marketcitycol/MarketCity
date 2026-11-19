<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envío de correos que nunca rompe una compra: si el servidor de correo falla, se registra en el log
 * y el pedido sigue su curso (lección del sitio anterior: un SMTP caído no puede frenar un pago).
 */
class Mailer
{
    public static function send(?string $to, Mailable $mail): bool
    {
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
