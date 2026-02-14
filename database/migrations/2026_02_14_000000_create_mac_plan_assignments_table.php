<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mac_plan_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('nas_identifier')->index();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('device_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique NAS identifier per router
            $table->unique(['nas_identifier', 'router_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mac_plan_assignments');
    }
};