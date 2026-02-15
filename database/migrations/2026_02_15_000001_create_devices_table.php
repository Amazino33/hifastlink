<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('mac', 50)->index();
            $table->foreignId('router_id')->nullable()->constrained('routers')->nullOnDelete();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('first_seen')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mac']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};