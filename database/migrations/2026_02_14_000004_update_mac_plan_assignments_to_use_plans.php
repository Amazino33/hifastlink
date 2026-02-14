<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, add the plan_id column
        Schema::table('mac_plan_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('mac_plan_assignments', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('router_id');
            }
        });

        // Migrate data: match data_plans to plans by name and set plan_id
        DB::statement("
            UPDATE mac_plan_assignments mpa
            INNER JOIN data_plans dp ON mpa.data_plan_id = dp.id
            INNER JOIN plans p ON dp.name = p.name
            SET mpa.plan_id = p.id
        ");

        // Make plan_id not nullable and add foreign key constraint
        Schema::table('mac_plan_assignments', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable(false)->change();
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        // Drop the old data_plan_id column
        Schema::table('mac_plan_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('mac_plan_assignments', 'data_plan_id')) {
                $table->dropForeign(['data_plan_id']);
                $table->dropColumn('data_plan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mac_plan_assignments', function (Blueprint $table) {
            // Reverse the migration
            if (!Schema::hasColumn('mac_plan_assignments', 'data_plan_id')) {
                $table->foreignId('data_plan_id')->nullable()->after('router_id');
            }

            // Migrate data back
            DB::statement("
                UPDATE mac_plan_assignments mpa
                INNER JOIN plans p ON mpa.plan_id = p.id
                INNER JOIN data_plans dp ON p.name = dp.name
                SET mpa.data_plan_id = dp.id
            ");

            // Drop plan_id and restore data_plan_id constraint
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
            $table->foreign('data_plan_id')->references('id')->on('data_plans')->onDelete('cascade');
        });
    }
};