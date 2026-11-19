<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Pausa los productos activos por debajo de $1.000 (precios de prueba o mal escritos). Se revisan en el backoffice. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('status', 'active')->where('price', '<', 1000)->update(['status' => 'paused', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Se reactivan a mano después de corregir el precio.
    }
};
