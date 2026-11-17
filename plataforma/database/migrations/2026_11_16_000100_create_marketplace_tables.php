<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo de datos de la versión 1 de MarketCity.
 *
 * Convenciones (ver docs/adr/0002-modelo-de-datos.md):
 * - Dinero en pesos colombianos (COP) como enteros, sin decimales.
 * - Fechas en UTC; la aplicación muestra America/Bogota.
 * - `legacy_id` guarda el id de WooCommerce/WCFM para la migración.
 * - El pedido guarda una copia del titular del comercio vigente (merchant_entity_id),
 *   así el titular puede cambiar (persona natural → sociedad) sin tocar pedidos viejos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Titular del comercio ante pasarelas, DIAN y clientes.
        Schema::create('merchant_entities', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('person_type', 20); // natural | juridica
            $table->string('document_type', 10); // CC | NIT
            $table->string('document_number', 30);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('department')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document_type', 10)->nullable();
            $table->string('document_number', 30)->nullable();
            $table->boolean('free_shipping')->default(true);
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->boolean('pickup_enabled')->default(false);
            $table->unsignedInteger('pickup_fee')->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending | active | suspended
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->nullable()->index();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('regular_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('color', 40)->nullable();
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('active')->index(); // draft | active | paused
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchant_entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_type', 10)->default('CC');
            $table->string('document_number', 30);
            $table->string('email');
            $table->string('phone', 30);
            $table->string('department');
            $table->string('city');
            $table->string('address');
            $table->string('address_2')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_total')->default(0);
            $table->unsignedInteger('total');
            $table->string('payment_method', 40);
            $table->string('payment_status', 20)->default('pending')->index(); // pending | paid | failed | refunded
            $table->timestamp('paid_at')->nullable();
            $table->string('terms_version', 20);
            $table->timestamp('terms_accepted_at');
            $table->boolean('marketing_optin')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('access_token', 64); // para ver el pedido sin cuenta
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        // Un pedido se divide en un subpedido por tienda: ahí viven envío, estado, comisión y liquidación.
        Schema::create('seller_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained();
            $table->unsignedInteger('subtotal');
            $table->string('shipping_method', 20); // envio | recogida
            $table->unsignedInteger('shipping_total')->default(0);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->unsignedInteger('seller_net')->default(0);
            $table->json('commission_rule')->nullable(); // copia de la regla aplicada
            $table->string('status', 20)->default('recibido')->index(); // recibido | validado | despachado | entregado | cancelado
            $table->string('carrier', 40)->nullable();
            $table->string('tracking_number', 60)->nullable();
            $table->date('settlement_due_on')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 64)->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // creado | pago | estado | guia | liquidacion
            $table->string('message');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40);
            $table->string('provider_reference')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('pending'); // pending | approved | declined
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'order_events', 'order_items', 'seller_orders', 'orders', 'products', 'brands', 'categories', 'stores', 'settings', 'merchant_entities'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
