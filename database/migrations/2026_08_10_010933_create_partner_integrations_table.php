<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // "Brothers Crib"
            $table->string('slug')->unique();                     // "brotherscrib" → /voucher/brotherscrib
            $table->string('api_url');                            // https://gameshop.com/api/voucher/redeem
            $table->string('api_key');                            // shared secret
            $table->string('code_field')->default('code');        // payload field name sent to partner API
            $table->string('primary_color')->default('#7c3aed');  // hex colour for portal branding
            $table->string('icon')->default('fa-solid fa-wifi');  // FontAwesome class
            $table->string('portal_title')->nullable();           // heading on captive page
            $table->string('portal_subtitle')->nullable();        // sub-heading
            $table->string('code_label')->default('Wi-Fi Code');  // form label
            $table->string('code_placeholder')->nullable();       // form placeholder
            $table->unsignedTinyInteger('code_maxlength')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_integrations');
    }
};
