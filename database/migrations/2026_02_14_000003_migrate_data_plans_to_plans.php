<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate data from data_plans to plans
        DB::statement("
            INSERT INTO plans (
                name, description, price, data_limit, duration_days, speed_limit,
                is_active, is_featured, sort_order, features, router_id, is_custom,
                show_universal_plans, created_at, updated_at
            )
            SELECT
                name, description, price, data_limit, duration_days, speed_limit,
                is_active, is_featured, sort_order, features, router_id, is_custom,
                show_universal_plans, created_at, updated_at
            FROM data_plans
            WHERE NOT EXISTS (
                SELECT 1 FROM plans WHERE plans.name = data_plans.name
            )
        ");
    }

    public function down(): void
    {
        // Remove migrated data (be careful with this)
        DB::statement("
            DELETE FROM plans
            WHERE id IN (
                SELECT p.id FROM plans p
                INNER JOIN data_plans dp ON p.name = dp.name
            )
        ");
    }
};