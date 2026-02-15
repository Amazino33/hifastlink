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

        // Migrate data from legacy `data_plan_id` only if the column & tables exist
        if (Schema::hasColumn('mac_plan_assignments', 'data_plan_id') && Schema::hasTable('data_plans') && Schema::hasTable('plans')) {
            DB::statement("UPDATE mac_plan_assignments mpa
                INNER JOIN data_plans dp ON mpa.data_plan_id = dp.id
                INNER JOIN plans p ON dp.name = p.name
                SET mpa.plan_id = p.id
                WHERE mpa.plan_id IS NULL");
        }

        // Add FK / make NOT NULL only when safe (no orphan references, no NULLs)
        if (Schema::hasColumn('mac_plan_assignments', 'plan_id')) {
            $orphanExists = DB::table('mac_plan_assignments')
                ->whereNotNull('plan_id')
                ->whereNotIn('plan_id', function ($q) { $q->select('id')->from('plans'); })
                ->exists();

            if (! $orphanExists) {
                try {
                    Schema::table('mac_plan_assignments', function (Blueprint $table) {
                        $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
                    });
                } catch (\Throwable $e) {
                    // ignore FK creation errors (may already exist)
                }

                $nullCount = DB::table('mac_plan_assignments')->whereNull('plan_id')->count();
                if ($nullCount === 0) {
                    try {
                        Schema::table('mac_plan_assignments', function (Blueprint $table) {
                            $table->foreignId('plan_id')->nullable(false)->change();
                        });
                    } catch (\Throwable $e) {
                        // ignore change errors
                    }
                }
            } else {
                \Log::warning('Skipping FK/not-null change for mac_plan_assignments.plan_id due to orphaned plan references');
            }
        }

        // Drop legacy `data_plan_id` if present
        if (Schema::hasColumn('mac_plan_assignments', 'data_plan_id')) {
            Schema::table('mac_plan_assignments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['data_plan_id']);
                } catch (\Throwable $e) {
                    // ignore if FK missing
                }
                $table->dropColumn('data_plan_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mac_plan_assignments', function (Blueprint $table) {
            // Reverse the migration
            if (!Schema::hasColumn('mac_plan_assignments', 'data_plan_id')) {
                $table->foreignId('data_plan_id')->nullable()->after('router_id');
            }

            // Migrate data back only if relevant columns/tables exist
            if (Schema::hasColumn('mac_plan_assignments', 'plan_id') && Schema::hasTable('plans') && Schema::hasTable('data_plans')) {
                DB::statement("UPDATE mac_plan_assignments mpa
                    INNER JOIN plans p ON mpa.plan_id = p.id
                    INNER JOIN data_plans dp ON p.name = dp.name
                    SET mpa.data_plan_id = dp.id
                    WHERE mpa.data_plan_id IS NULL");
            }

            // Drop plan_id (if exists) and restore data_plan_id FK when safe
            if (Schema::hasColumn('mac_plan_assignments', 'plan_id')) {
                try {
                    $table->dropForeign(['plan_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('plan_id');
            }

            if (Schema::hasColumn('mac_plan_assignments', 'data_plan_id') && Schema::hasTable('data_plans')) {
                try {
                    $table->foreign('data_plan_id')->references('id')->on('data_plans')->onDelete('cascade');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
};