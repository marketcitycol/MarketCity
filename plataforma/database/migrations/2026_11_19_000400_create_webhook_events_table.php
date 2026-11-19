<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Avisos de las pasarelas: cada uno se guarda una sola vez (idempotencia) con su resultado, para auditar y conciliar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('event_key', 191);
            $table->boolean('signature_valid')->default(false);
            $table->json('payload')->nullable();
            $table->string('result', 300)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
