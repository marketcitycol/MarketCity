<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traslado de los snippets de WordPress, ola S1 (ver docs/producto/traslado-snippets.md):
 * - Campañas con precio programado y tope de unidades (#16, #77, #78).
 * - Precio con correo: cupón único por correo y producto (#76).
 * - Permisos de mensajes por canal y registro de contactos (#82, Ley 1581 y Ley 2300).
 * - Carritos abandonados con recordatorios (#83).
 * - Datos del navegador en el pedido para la compra de Meta solo con pago confirmado (#85).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('badge', 30)->nullable(); // etiqueta en las tarjetas, p. ej. BLACK CITY
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('unit_cap')->nullable(); // unidades por producto a precio de campaña (null = sin tope)
            $table->boolean('active')->default(true);
            $table->boolean('email_price_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('campaign_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('campaign_price');
            $table->unsignedInteger('email_price')->nullable(); // precio con correo, menor que el de campaña
            $table->unsignedInteger('base_price')->nullable(); // precio normal guardado mientras se aplica la campaña
            $table->unsignedInteger('base_regular_price')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('closed_at')->nullable(); // se llenó el tope
            $table->timestamps();
            $table->unique(['campaign_id', 'product_id']);
        });

        Schema::create('email_price_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('email')->nullable(); // se borra 30 días después de la campaña si no se usó (Ley 1581)
            $table->string('email_hash', 64)->index();
            $table->string('code', 20)->unique();
            $table->unsignedInteger('promised_price');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('consent_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'product_id', 'email_hash']);
        });

        // Registro inmutable de autorizaciones y bajas: cada fila es un hecho con fecha y texto exacto.
        Schema::create('marketing_consents', function (Blueprint $table) {
            $table->id();
            $table->string('contact', 120)->index(); // correo en minúsculas o celular 57XXXXXXXXXX
            $table->string('channel', 20); // email | whatsapp
            $table->string('purpose', 20); // carrito | ofertas
            $table->boolean('granted');
            $table->string('source', 40); // checkout | baja | backoffice | ...
            $table->string('text_version', 20)->nullable();
            $table->string('text_hash', 64)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['contact', 'channel', 'purpose', 'id']);
        });

        // Mensajes publicitarios enviados: máximo uno al día por persona y el mismo canal por 7 días.
        Schema::create('marketing_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 120)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('channel', 20);
            $table->string('source', 40);
            $table->string('reference', 60)->nullable();
            $table->timestamp('sent_at')->index();
        });

        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('session_id', 100)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email', 120)->nullable()->index();
            $table->string('phone', 20)->nullable();
            $table->string('name', 80)->nullable();
            $table->string('channel', 20); // email | whatsapp
            $table->json('items'); // {product_id: cantidad}
            $table->unsignedInteger('total')->default(0);
            $table->string('status', 20)->default('open')->index(); // open | recovered | stopped | done
            $table->string('stop_reason', 40)->nullable();
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('next_reminder_at')->nullable()->index();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->after('campaign_id')->constrained('email_price_coupons')->nullOnDelete();
            $table->unsignedInteger('discount_total')->default(0)->after('line_total');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable()->after('ip_address');
            $table->string('fbp', 120)->nullable()->after('user_agent');
            $table->string('fbc', 255)->nullable()->after('fbp');
            $table->boolean('analytics_consent')->default(false)->after('fbc');
            $table->timestamp('meta_purchase_sent_at')->nullable()->after('analytics_consent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'fbp', 'fbc', 'analytics_consent', 'meta_purchase_sent_at']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn('discount_total');
        });
        foreach (['abandoned_carts', 'marketing_contacts', 'marketing_consents', 'email_price_coupons', 'campaign_products', 'campaigns'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
