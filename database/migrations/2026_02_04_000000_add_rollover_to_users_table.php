<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'rollover_available_bytes')) {
                $table->bigInteger('rollover_available_bytes')->default(0)->after('data_limit');
            }

            if (!Schema::hasColumn('users', 'rollover_validity_days')) {
                $table->integer('rollover_validity_days')->nullable()->after('rollover_available_bytes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'rollover_available_bytes')) {
                $table->dropColumn('rollover_available_bytes');
            }

            if (Schema::hasColumn('users', 'rollover_validity_days')) {
                $table->dropColumn('rollover_validity_days');
            }
        });
    }
};