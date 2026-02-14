<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'router_id')) {
                $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('set null')->after('rollover_validity_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'router_id')) {
                $table->dropForeign(['router_id']);
                $table->dropColumn('router_id');
            }
        });
    }
};