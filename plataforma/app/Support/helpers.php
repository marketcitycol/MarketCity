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

if (! function_exists('installments')) {
    /** Número de cuotas que se muestra como referencia en catálogo (configurable). */
    function installments(): int
    {
        return max(1, (int) \App\Models\Setting::get('installments', 4));
    }
}
