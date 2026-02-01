<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Night Owl (11PM-6AM)',
            'price' => 200,
            'data_limit' => 5 * 1024 * 1024 * 1024, // 5GB in bytes
            'validity_days' => 1,
            'allowed_login_time' => 'Al2300-0600',
        ]);

        Plan::create([
            'name' => 'Weekend Binge',
            'price' => 500,
            'data_limit' => 10 * 1024 * 1024 * 1024, // 10GB in bytes
            'validity_days' => 2,
            'allowed_login_time' => 'SaSu0000-2400',
        ]);

        Plan::create([
            'name' => '1 Hour Quick',
            'price' => 100,
            'data_limit' => 1 * 1024 * 1024 * 1024, // 1GB in bytes
            'validity_days' => 1/24, // 1 hour
            'allowed_login_time' => null,
        ]);

        Plan::create([
            'name' => 'Standard Monthly',
            'price' => 5000,
            'data_limit' => 50 * 1024 * 1024 * 1024, // 50GB in bytes
            'validity_days' => 30,
            'allowed_login_time' => null,
        ]);
    }
}