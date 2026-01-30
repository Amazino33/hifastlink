<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            // Rename 'days' column to 'duration_days' if it exists
            if (Schema::hasColumn('data_plans', 'days')) {
                $table->renameColumn('days', 'duration_days');
            }
            
            // Add new columns if they don't exist
            if (!Schema::hasColumn('data_plans', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('data_plans', 'speed_limit')) {
                $table->string('speed_limit')->default('10M/10M')->after('price');
            }
            
            if (!Schema::hasColumn('data_plans', 'features')) {
                $table->json('features')->nullable()->after('sort_order');
            }
            
            // Add index if it doesn't exist
            if (!Schema::hasIndex('data_plans', 'data_plans_is_active_sort_order_index')) {
                $table->index(['is_active', 'sort_order']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            // Reverse the changes
            if (Schema::hasColumn('data_plans', 'duration_days')) {
                $table->renameColumn('duration_days', 'days');
            }
            
            $table->dropColumn(['description', 'speed_limit', 'features']);
            $table->dropIndex(['is_active', 'sort_order']);
        });
    }
};