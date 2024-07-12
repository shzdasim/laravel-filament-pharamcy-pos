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
        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('posted_number');
            $table->date('date');
            $table->string('remarks')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('patient_name')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0)->nullable();
            $table->decimal('tax_percentage', 5, 2)->default(0)->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0)->nullable();
            $table->decimal('item_discount', 10, 2)->default(0)->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_invoices');
    }
};
