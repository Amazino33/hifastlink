<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AffiliateRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Role::class)) return;

        Role::firstOrCreate(['name' => 'affiliate']);
    }
}
