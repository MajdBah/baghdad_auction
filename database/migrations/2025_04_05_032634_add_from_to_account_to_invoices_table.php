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
            // إضافة حقول لتمثيل حساب المصدر والوجهة للفاتورة
            $table->foreignId('from_account_id')->nullable()->after('account_id')
                  ->constrained('accounts')->onDelete('restrict');
            $table->foreignId('to_account_id')->nullable()->after('from_account_id')
                  ->constrained('accounts')->onDelete('restrict');

            // إضافة حقل direction لتحديد اتجاه الفاتورة بالنسبة لحساب الوسيط
            $table->enum('direction', ['positive', 'negative'])->nullable()->after('to_account_id')
                  ->comment('موجب أو سالب بالنسبة لحساب الوسيط');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['from_account_id']);
            $table->dropForeign(['to_account_id']);
            $table->dropColumn(['from_account_id', 'to_account_id', 'direction']);
        });
    }
};
