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
        Schema::table('routers', function (Blueprint $table) {
            $table->string('brand_name')->nullable()->after('owner_id');
            $table->string('brand_logo')->nullable()->after('brand_name');
            $table->string('brand_favicon')->nullable()->after('brand_logo');
            $table->string('brand_color', 7)->nullable()->after('brand_favicon');
            $table->string('brand_tagline')->nullable()->after('brand_color');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['brand_name', 'brand_logo', 'brand_favicon', 'brand_color', 'brand_tagline']);
        });
    }
};
