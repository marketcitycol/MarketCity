<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Preguntas al vendedor desde la página del producto (en WCFM: «Consultas» / «Enquiry»). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->string('email', 150);
            $table->string('question', 500);
            $table->string('answer', 1000)->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('answered_at')->nullable();
            $table->boolean('hidden')->default(false); // el Backoffice la oculta de la página del producto
            $table->timestamps();
            $table->index(['store_id', 'answered_at']);
            $table->index(['product_id', 'hidden', 'answered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
    }
};
