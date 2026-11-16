<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Condiciones negociadas con cada comercio: comisión propia y medios de pago permitidos. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->nullable()->after('pickup_fee'); // null = la general
            $table->unsignedInteger('commission_fixed')->nullable()->after('commission_percent');
            $table->string('payment_methods')->nullable()->after('commission_fixed'); // null = todos los activos
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'commission_fixed', 'payment_methods']);
        });
    }
};
