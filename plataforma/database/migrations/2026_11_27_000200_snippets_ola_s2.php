<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traslado de los snippets de WordPress, ola S2 (ver docs/producto/traslado-snippets.md):
 * - Mi cuenta: direcciones guardadas y favoritos (#48, #49, #79).
 * - Directorio: destacar u ocultar tiendas (#68).
 * - Perfil de vendedor: logística y notificaciones (#69).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->nullable(); // Casa, Oficina...
            $table->string('recipient', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('department', 60);
            $table->string('city', 80);
            $table->string('address', 150);
            $table->string('address_2', 150)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('verified_at'); // destacada en /tiendas
            $table->boolean('directory_hidden')->default(false)->after('featured'); // fuera de /tiendas (p. ej. tiendas de prueba)
            $table->unsignedTinyInteger('dispatch_days')->default(2)->after('pickup_fee'); // días hábiles para despachar con el pago confirmado
            $table->string('carriers', 150)->nullable()->after('dispatch_days'); // transportadoras con las que despacha
            $table->string('notify_email', 120)->nullable()->after('carriers'); // correo para avisos (vacío = el de la cuenta)
            $table->boolean('notify_new_order')->default(true)->after('notify_email');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['featured', 'directory_hidden', 'dispatch_days', 'carriers', 'notify_email', 'notify_new_order']);
        });
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('addresses');
    }
};
