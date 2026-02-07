<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Uyo Hub"
            $table->string('location'); // Address
            $table->string('ip_address')->unique(); // Router's IP
            $table->string('nas_identifier')->unique(); // e.g., "router_uyo_01"
            $table->string('secret'); // RADIUS secret
            $table->string('api_user')->nullable(); // MikroTik API username
            $table->string('api_password')->nullable(); // MikroTik API password
            $table->integer('api_port')->default(8728); // MikroTik API port
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
