<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        // Add unique index on phone (skip nulls)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn('phone_verified_at');
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
        });
    }
};
