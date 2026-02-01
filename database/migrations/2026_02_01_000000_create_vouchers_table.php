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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // The secret code (e.g. 8493-2219)
            $table->foreignId('plan_id')->constrained()->onDelete('cascade'); // What plan does this give?
            $table->boolean('is_used')->default(false);
            $table->foreignId('used_by')->nullable()->constrained('users'); // Who used it?
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};