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
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('label', 100)->nullable()->after('data_limit_mb');
            $table->boolean('is_unlimited')->default(false)->after('label');
            $table->unsignedInteger('speed_limit_upload')->nullable()->after('is_unlimited');
            $table->unsignedInteger('speed_limit_download')->nullable()->after('speed_limit_upload');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['label', 'is_unlimited', 'speed_limit_upload', 'speed_limit_download']);
        });
    }
};
