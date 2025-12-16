<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'data_used')) {
                $table->bigInteger('data_used')->default(0)->after('phone');
            }
            if (!Schema::hasColumn('users', 'data_limit')) {
                $table->bigInteger('data_limit')->default(0)->after('data_used');
            }
            if (!Schema::hasColumn('users', 'subscription_end_date')) {
                $table->timestamp('subscription_end_date')->nullable()->after('data_limit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'data_used', 'data_limit', 'subscription_end_date']);
        });
    }
};