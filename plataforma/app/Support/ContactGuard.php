<?php

namespace App\Support;

/**
 * Las preguntas y respuestas son públicas y la venta se cierra en MarketCity: no se aceptan
 * correos, teléfonos, WhatsApp ni enlaces (en WCFM: «ocultar datos de contacto del vendedor»).
 */
class ContactGuard
{
    public static function hasContactData(string $text): bool
    {
        $t = mb_strtolower($text);
        if (preg_match('/[a-z0-9._%+-]+\s*(@|\(at\)|\[at\]| arroba )\s*[a-z0-9.-]+\.[a-z]{2,}/u', $t)) {
            return true;
        }
        if (preg_match('/(https?:\/\/|www\.|wa\.me|whatsapp|t\.me\/|instagram\.com|facebook\.com)/u', $t)) {
            return true;
        }

        // Celulares (3xx) y fijos (60x) de Colombia, con o sin +57 y separadores; no confunde precios.
        return (bool) preg_match('/(?<!\d)(?:\+?\s*57[\s.\-]*)?(?:3\d{2}|60\d)[\s.\-]*\d{3}[\s.\-]*\d{2}[\s.\-]*\d{2}(?!\d)/u', $t);
    }
}
