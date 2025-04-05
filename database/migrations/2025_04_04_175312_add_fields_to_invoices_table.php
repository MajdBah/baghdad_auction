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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_fee', 15, 2)->default(0)->after('tax_amount');
            $table->foreignId('car_id')->nullable()->after('account_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount', 'shipping_fee']);
            $table->dropForeign(['car_id']);
            $table->dropColumn('car_id');
        });
    }
};
