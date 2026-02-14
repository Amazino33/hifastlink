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
        Schema::table('data_plans', function (Blueprint $table) {
            if (Schema::hasColumn('data_plans', 'duration_days')) {
                $table->renameColumn('duration_days', 'validity_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            if (Schema::hasColumn('data_plans', 'validity_days')) {
                $table->renameColumn('validity_days', 'duration_days');
            }
        });
    }
};