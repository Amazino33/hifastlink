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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pending_plan_id']);
            $table->dropColumn(['pending_plan_id', 'pending_plan_purchased_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pending_plan_id')->nullable()->constrained('plans');
            $table->timestamp('pending_plan_purchased_at')->nullable();
        });
    }
};
