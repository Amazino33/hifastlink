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
        Schema::table('users', function (Blueprint $table) {
            // Add username if it doesn't exist (without unique constraint if it already exists)
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('email');
            }
            
            // Add data_used if it doesn't exist
            if (!Schema::hasColumn('users', 'data_used')) {
                $table->bigInteger('data_used')->default(0)->after('username');
            }
            
            // Add online_status
            if (!Schema::hasColumn('users', 'online_status')) {
                $table->boolean('online_status')->default(false)->after('data_used');
            }
            
            // Add plan_expiry
            if (!Schema::hasColumn('users', 'plan_expiry')) {
                $table->timestamp('plan_expiry')->nullable()->after('online_status');
            }
            
            // Add simultaneous_sessions
            if (!Schema::hasColumn('users', 'simultaneous_sessions')) {
                $table->integer('simultaneous_sessions')->default(1)->after('plan_expiry');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'online_status',
                'plan_expiry',
                'simultaneous_sessions'
            ]);
        });
    }
};
