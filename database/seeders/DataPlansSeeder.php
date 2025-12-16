<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Plan',
                'days' => 30,
                'data_limit' => 10 * 1024 * 1024 * 1024, // 10GB
                'price' => 5000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Basic Plan',
                'days' => 30,
                'data_limit' => 20 * 1024 * 1024 * 1024, // 20GB
                'price' => 8000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Standard Plan',
                'days' => 30,
                'data_limit' => 50 * 1024 * 1024 * 1024, // 50GB
                'price' => 15000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Premium Plan',
                'days' => 30,
                'data_limit' => 100 * 1024 * 1024 * 1024, // 100GB
                'price' => 25000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4
            ],
            [
                'name' => 'Weekly Lite',
                'days' => 7,
                'data_limit' => 5 * 1024 * 1024 * 1024, // 5GB
                'price' => 2000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 5
            ],
            [
                'name' => 'Weekly Plus',
                'days' => 7,
                'data_limit' => 10 * 1024 * 1024 * 1024, // 10GB
                'price' => 3500,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 6
            ],
            [
                'name' => 'Ultimate Plan',
                'days' => 30,
                'data_limit' => 0, // Unlimited
                'price' => 40000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 7
            ],
            [
                'name' => 'Student Plan',
                'days' => 30,
                'data_limit' => 30 * 1024 * 1024 * 1024, // 30GB
                'price' => 10000,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 8
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('data_plans')->insert([
                'name' => $plan['name'],
                'days' => $plan['days'],
                'data_limit' => $plan['data_limit'],
                'price' => $plan['price'],
                'is_active' => $plan['is_active'],
                'is_featured' => $plan['is_featured'],
                'sort_order' => $plan['sort_order'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}