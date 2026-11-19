<?php

if (! function_exists('cop')) {
    /** Formatea pesos colombianos: 1234567 → "$ 1.234.567". */
    function cop(int|float|null $amount): string
    {
        return '$ '.number_format((int) $amount, 0, ',', '.');
    }
}

if (! function_exists('fecha')) {
    /** Muestra una fecha guardada en UTC en hora de Colombia. */
    function fecha(?\DateTimeInterface $date, string $format = 'd/m/Y g:i a'): string
    {
        return $date ? \Carbon\Carbon::instance($date)->setTimezone('America/Bogota')->format($format) : '—';
    }
}

if (! function_exists('mask_document')) {
    /** Muestra solo los últimos 4 caracteres de un documento: 1012345678 → ••••••5678. */
    function mask_document(?string $document): string
    {
        $document = trim((string) $document);
        if ($document === '') {
            return '';
        }
        $visible = mb_substr($document, -4);

        return str_repeat('•', max(2, mb_strlen($document) - 4)).$visible;
    }
}

if (! function_exists('installments')) {
    /** Número de cuotas que se muestra como referencia en catálogo (configurable). */
    function installments(): int
    {
        return max(1, (int) \App\Models\Setting::get('installments', 4));
    }
}

if (! function_exists('icon')) {
    /** SVG de línea de MarketCity: {!! icon('carrito', 'h-6 w-6') !!} */
    function icon(string $name, string $class = 'h-5 w-5'): string
    {
        return \App\Support\Icons::svg($name, $class);
    }
}
