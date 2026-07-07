<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error')->index();   // error | warning | info
            $table->string('type', 255)->nullable()->index();          // exception class name
            $table->text('message');
            $table->json('context')->nullable();                       // trace, extra data
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
