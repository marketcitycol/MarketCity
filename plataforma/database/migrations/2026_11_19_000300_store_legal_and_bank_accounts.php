<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos legales de la tienda y cuenta bancaria con revisión (lección del sitio anterior: un vendedor podía cambiar
 * su cuenta sin contraseña ni revisión, la vía más común para desviar pagos). Cada cambio de cuenta es una
 * solicitud que MarketCity aprueba; mientras haya una pendiente, las liquidaciones de esa tienda quedan retenidas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('legal_name', 150)->nullable()->after('name');
            $table->string('rut_path')->nullable()->after('document_number');
            $table->timestamp('verified_at')->nullable()->after('status');
        });

        Schema::create('store_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('bank', 60);
            $table->string('account_type', 20); // Ahorros | Corriente | Depósito electrónico
            $table->text('account_number'); // cifrado
            $table->string('holder_name', 150);
            $table->string('holder_document_type', 10);
            $table->text('holder_document_number'); // cifrado
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected | replaced
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 300)->nullable();
            $table->string('submitted_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_bank_accounts');
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'rut_path', 'verified_at']);
        });
    }
};
