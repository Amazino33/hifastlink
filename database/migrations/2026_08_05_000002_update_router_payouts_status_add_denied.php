<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE router_payouts MODIFY COLUMN status ENUM('pending','paid','denied') NOT NULL DEFAULT 'pending'");

        Schema::table('router_payouts', function (Blueprint $table) {
            $table->string('denied_reason')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('router_payouts', function (Blueprint $table) {
            $table->dropColumn('denied_reason');
        });

        DB::statement("ALTER TABLE router_payouts MODIFY COLUMN status ENUM('pending','paid') NOT NULL DEFAULT 'pending'");
    }
};
