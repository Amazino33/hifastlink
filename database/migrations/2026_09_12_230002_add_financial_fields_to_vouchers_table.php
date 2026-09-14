<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('id')->constrained('voucher_batches')->onDelete('set null');
            $table->decimal('price', 10, 2)->default(0.00)->after('plan_id');
            $table->foreignId('transaction_id')->nullable()->after('price')->constrained('transactions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
            $table->dropForeign(['transaction_id']);
            $table->dropColumn(['price', 'transaction_id']);
        });
    }
};
