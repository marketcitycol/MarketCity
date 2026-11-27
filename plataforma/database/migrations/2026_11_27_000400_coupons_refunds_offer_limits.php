<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pendientes P1 frente a WooCommerce: cupones generales, reembolsos y límite por cliente en las
 * ofertas relámpago (campañas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('description', 200)->nullable();
            $table->string('type', 20); // percent | fixed | free_shipping
            $table->unsignedInteger('amount')->default(0); // % o pesos
            $table->unsignedInteger('max_discount')->nullable(); // tope en pesos del descuento por porcentaje
            $table->unsignedInteger('min_subtotal')->default(0);
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete(); // null = toda la tienda (lo asume MarketCity)
            $table->boolean('exclude_sale_items')->default(false);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('legacy_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('shipping_total')->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable()->after('coupon_id');
            $table->unsignedInteger('discount_total')->default(0)->after('coupon_code');
            $table->unsignedInteger('refunded_total')->default(0)->after('total');
        });

        Schema::table('seller_orders', function (Blueprint $table) {
            $table->unsignedInteger('discount_total')->default(0)->after('shipping_total');
            $table->string('discount_funded_by', 20)->nullable()->after('discount_total'); // marketcity | tienda
            $table->unsignedInteger('refunded_total')->default(0)->after('seller_net');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('reason', 300);
            $table->string('charged_to', 20); // tienda | marketcity
            $table->unsignedInteger('commission_reversed')->default(0);
            $table->unsignedInteger('seller_debit')->default(0);
            $table->boolean('pending_deduction')->default(false); // el subpedido ya estaba liquidado
            $table->boolean('restocked')->default(false);
            $table->string('reference', 120)->nullable(); // referencia del reembolso en la pasarela o el banco
            $table->timestamps();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedInteger('per_customer_limit')->nullable()->after('unit_cap');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn('per_customer_limit'));
        Schema::dropIfExists('refunds');
        Schema::table('seller_orders', fn (Blueprint $t) => $t->dropColumn(['discount_total', 'discount_funded_by', 'refunded_total']));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('coupon_id');
            $t->dropColumn(['coupon_code', 'discount_total', 'refunded_total']);
        });
        Schema::dropIfExists('coupons');
    }
};
