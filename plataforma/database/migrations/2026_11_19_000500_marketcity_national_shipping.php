<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La tienda MarketCity copiada del sitio anterior quedó con "envío gratis" porque era el valor por omisión.
 * En el sitio actual el envío es la tarifa plana "Nacional" de $15.000 y el envío gratis está apagado.
 * Solo se corrige si nadie la ha configurado (gratis y en $0); el vendedor o el backoffice pueden cambiarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'shipping_national_fee'], ['value' => '15000', 'updated_at' => now(), 'created_at' => now()]);
        DB::table('stores')->where('slug', 'marketcity')->where('free_shipping', true)->where('shipping_fee', 0)
            ->update(['free_shipping' => false, 'shipping_fee' => 15000, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Sin reversión automática: se ajusta en "Mi tienda".
    }
};
