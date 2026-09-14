<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('set null');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_cost', 10, 2)->default(0.00);
            $table->string('payment_method')->default('wallet'); // wallet, paystack, admin_override
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->string('status')->default('completed'); // completed, pending_payment, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
