<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('username');
            $table->string('router_name');
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('profile')->nullable();
            $table->string('uptime')->nullable();
            $table->bigInteger('bytes_in')->default(0);
            $table->bigInteger('bytes_out')->default(0);
            $table->bigInteger('used_bytes')->default(0);
            $table->bigInteger('limit_bytes')->default(0);
            $table->bigInteger('remaining_bytes')->default(-1);
            $table->timestamp('session_timestamp');
            $table->timestamps();
            
            $table->index(['user_id', 'session_timestamp']);
            $table->index('username');
            $table->index('router_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
