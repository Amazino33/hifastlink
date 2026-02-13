<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->foreignId('router_id')->nullable()->constrained('routers')->onDelete('cascade');
            $table->boolean('is_custom')->default(false);
            $table->boolean('show_universal_plans')->default(false); // Whether to show universal plans alongside custom ones
        });
    }

    public function down(): void
    {
        Schema::table('data_plans', function (Blueprint $table) {
            $table->dropForeign(['router_id']);
            $table->dropColumn(['router_id', 'is_custom', 'show_universal_plans']);
        });
    }
};