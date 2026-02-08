<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (! Schema::hasColumn('routers', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('routers', function (Blueprint $table) {
            if (Schema::hasColumn('routers', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
