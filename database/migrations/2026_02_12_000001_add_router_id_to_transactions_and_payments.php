<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete()->after('plan_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete()->after('plan_name');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('router_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('router_id');
        });
    }
};