<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Descuentos pendientes en las liquidaciones: lo que se le reembolsó al cliente de un subpedido que
 * ya se le había pagado a la tienda se resta de sus siguientes liquidaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->unsignedInteger('deducted_amount')->default(0)->after('pending_deduction'); // cuánto ya se descontó
            $table->timestamp('deducted_at')->nullable()->after('deducted_amount'); // cuándo quedó descontado del todo
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->unsignedInteger('settlement_deduction')->default(0)->after('settlement_reference'); // restado al liquidar
        });
    }

    public function down(): void
    {
        Schema::table('seller_orders', fn (Blueprint $t) => $t->dropColumn('settlement_deduction'));
        Schema::table('refunds', fn (Blueprint $t) => $t->dropColumn(['deducted_amount', 'deducted_at']));
    }
};
