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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();
            $table->decimal('intermediary_profit', 15, 2)->nullable();
            $table->string('auction_name')->nullable();
            $table->string('auction_lot_number')->nullable();
            $table->foreignId('customer_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('shipping_company_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status'); // purchased, shipped, delivered, sold
            $table->date('purchase_date');
            $table->date('shipping_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
