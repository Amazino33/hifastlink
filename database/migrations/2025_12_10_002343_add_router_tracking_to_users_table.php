<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('phone');
            $table->bigInteger('data_used')->default(0)->after('username'); // in bytes
            $table->bigInteger('data_limit')->default(0)->after('data_used'); // in bytes (0 = unlimited)
            $table->timestamp('last_online')->nullable()->after('data_limit');
            $table->string('connection_status')->default('inactive')->after('last_online'); // active, inactive, suspended
            $table->string('current_ip')->nullable()->after('connection_status');
            $table->decimal('current_speed', 8, 2)->default(0)->after('current_ip'); // Mbps
            $table->integer('subscription_days')->default(0)->after('current_speed');
            $table->date('subscription_expires')->nullable()->after('subscription_days');
            
            $table->index('phone');
            $table->index('username');
            $table->index('connection_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'data_used', 'data_limit', 
                'last_online', 'connection_status', 'current_ip', 
                'current_speed', 'subscription_days', 'subscription_expires'
            ]);
        });
    }
};
