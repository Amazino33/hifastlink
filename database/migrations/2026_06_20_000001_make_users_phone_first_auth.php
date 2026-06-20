<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deduplicate phone numbers before adding unique constraint:
        // keep the most recently active row, null out the rest.
        $dupes = DB::table('users')
            ->select('phone', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->having('cnt', '>', 1)
            ->pluck('phone');

        foreach ($dupes as $phone) {
            $keep = DB::table('users')
                ->where('phone', $phone)
                ->orderByDesc('last_online')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            DB::table('users')
                ->where('phone', $phone)
                ->where('id', '!=', $keep)
                ->update(['phone' => null]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

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
