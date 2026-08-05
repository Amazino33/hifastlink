<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            // Per-router opt-in: if true, all users on this router lose access when the owner's plan expires
            $table->boolean('requires_owner_subscription')->default(false)->after('owner_id');
            // Runtime state: true while the gate is actively blocking (set by the scheduled command)
            $table->boolean('access_blocked')->default(false)->after('requires_owner_subscription');
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            $table->dropColumn(['requires_owner_subscription', 'access_blocked']);
        });
    }
};
