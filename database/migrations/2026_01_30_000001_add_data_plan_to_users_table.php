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
            // Add data plan relationship
            $table->foreignId('data_plan_id')->nullable()->after('username')->constrained('data_plans')->nullOnDelete();
            
            // Add subscription start date
            $table->timestamp('subscription_start_date')->nullable()->after('data_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['data_plan_id']);
            $table->dropColumn(['data_plan_id', 'subscription_start_date']);
        });
    }
};
