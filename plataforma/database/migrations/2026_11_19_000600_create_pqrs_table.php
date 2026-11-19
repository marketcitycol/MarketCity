<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PQRS con radicado (peticiones, quejas, reclamos, sugerencias, retracto y reversión del pago),
 * y fecha de entrega de cada subpedido para contar el plazo de retracto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pqrs', function (Blueprint $table) {
            $table->id();
            $table->string('radicado', 24)->unique();
            $table->string('type', 20)->index();
            $table->string('status', 20)->default('recibida')->index();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30)->nullable();
            $table->text('document_number')->nullable(); // cifrado
            $table->string('order_number', 20)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->boolean('within_term')->nullable(); // retracto/reversión: ¿dentro del plazo legal?
            $table->timestamp('due_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_consent_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('seller_orders', fn (Blueprint $table) => $table->dropColumn('delivered_at'));
        Schema::dropIfExists('pqrs');
    }
};
