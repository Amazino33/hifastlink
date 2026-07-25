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
            $table->string('brand_bg_color')->nullable()->after('brand_layout');
            $table->string('brand_heading')->nullable()->after('brand_bg_color');
            $table->string('brand_subheading')->nullable()->after('brand_heading');
            $table->string('brand_button_text')->nullable()->after('brand_subheading');
            $table->string('brand_help_text')->nullable()->after('brand_button_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn([
                'brand_bg_color',
                'brand_heading',
                'brand_subheading',
                'brand_button_text',
                'brand_help_text',
            ]);
        });
    }
};
