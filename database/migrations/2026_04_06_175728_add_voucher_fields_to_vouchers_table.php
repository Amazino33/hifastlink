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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('plan_id');
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete()->after('created_by');
            $table->unsignedInteger('duration_hours')->default(24)->after('router_id');
            $table->unsignedBigInteger('data_limit_mb')->nullable()->after('duration_hours');
            $table->unsignedTinyInteger('max_uses')->default(1)->after('data_limit_mb');
            $table->unsignedTinyInteger('used_count')->default(0)->after('max_uses');
            $table->timestamp('expires_at')->nullable()->after('used_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('router_id');
            $table->dropColumn(['duration_hours', 'data_limit_mb', 'max_uses', 'used_count', 'expires_at']);
        });
    }
};
