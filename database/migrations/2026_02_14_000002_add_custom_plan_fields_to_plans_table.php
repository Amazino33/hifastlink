<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Add fields from data_plans that are missing in plans
            if (!Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (!Schema::hasColumn('plans', 'duration_days')) {
                $table->integer('duration_days')->default(30)->after('validity_days');
            }

            if (!Schema::hasColumn('plans', 'speed_limit')) {
                $table->string('speed_limit')->default('10M/10M')->after('speed_limit_download');
            }

            if (!Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_devices');
            }

            if (!Schema::hasColumn('plans', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_featured');
            }

            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('sort_order');
            }

            if (!Schema::hasColumn('plans', 'router_id')) {
                $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('set null')->after('features');
            }

            if (!Schema::hasColumn('plans', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('router_id');
            }

            if (!Schema::hasColumn('plans', 'show_universal_plans')) {
                $table->boolean('show_universal_plans')->default(false)->after('is_custom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'duration_days',
                'speed_limit',
                'is_active',
                'is_featured',
                'sort_order',
                'features',
                'router_id',
                'is_custom',
                'show_universal_plans'
            ]);
        });
    }
};