<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('data_limit')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert to NOT NULL with default 0; coerce existing NULLs first
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE users SET data_limit = 0 WHERE data_limit IS NULL'
            );
            $table->bigInteger('data_limit')->nullable(false)->default(0)->change();
        });
    }
};
