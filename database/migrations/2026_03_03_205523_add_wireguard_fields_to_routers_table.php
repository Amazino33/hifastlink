<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replace 'routers' with your actual table name if it is different (e.g., 'nas')
        Schema::table('routers', function (Blueprint $table) {
            $table->string('wireguard_public_key')->nullable()->after('nas_identifier');
            // If you don't have a specific column for the VPN IP yet, add this too:
            // $table->string('vpn_ip')->nullable()->after('wireguard_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn('wireguard_public_key');
            // $table->dropColumn('vpn_ip');
        });
    }
};