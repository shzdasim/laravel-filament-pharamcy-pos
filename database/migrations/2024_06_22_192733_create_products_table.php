<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique();
            $table->string('name')->unique();
            $table->string('image')->nullable();
            $table->string('formulation')->nullable();
            $table->text('description')->nullable();
            $table->integer('pack_size');
            $table->integer('quantity')->nullable();
            $table->decimal('pack_purchase_price', 10, 2)->nullable();
            $table->decimal('pack_sale_price', 10, 2)->nullable();
            $table->decimal('unit_purchase_price', 10, 2)->nullable();
            $table->decimal('unit_sale_price', 10, 2)->nullable();
            $table->decimal('avg_price', 10, 2)->nullable();
            $table->boolean('narcotic')->default(0);
            $table->decimal('max_discount', 5, 2)->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
