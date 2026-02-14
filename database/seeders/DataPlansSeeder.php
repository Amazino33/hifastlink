<?php

namespace Database\Seeders;

use App\Models\DataPlan;
use Illuminate\Database\Seeder;

class DataPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing plans
        DataPlan::truncate();

        $plans = [
            [
                'name' => 'Basic Plan',
                'description' => 'Perfect for light browsing and email',
                'data_limit' => 1073741824, // 1GB
                'validity_days' => 30,
                'price' => 2500.00,
                'speed_limit' => '5M/5M',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => ['24/7 Support', 'Static IP', 'No FUP']
            ],
            [
                'name' => 'Standard Plan',
                'description' => 'Great for streaming and work from home',
                'data_limit' => 5368709120, // 5GB
                'validity_days' => 30,
                'price' => 5000.00,
                'speed_limit' => '10M/10M',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => ['HD Streaming', 'Priority Support', 'Static IP', 'No FUP']
            ],
            [
                'name' => 'Premium Plan',
                'description' => 'Ultimate speed for heavy users',
                'data_limit' => 10737418240, // 10GB
                'validity_days' => 30,
                'price' => 8000.00,
                'speed_limit' => '20M/20M',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'features' => ['4K Streaming', 'VIP Support', 'Static IP', 'No FUP', 'Free Router']
            ],
            [
                'name' => 'Business Plan',
                'description' => 'Dedicated connection for businesses',
                'data_limit' => 53687091200, // 50GB
                'validity_days' => 30,
                'price' => 15000.00,
                'speed_limit' => '50M/50M',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'features' => ['99.9% Uptime SLA', 'Business Support', 'Static IP', 'No FUP', 'Free Installation']
            ]
        ];

        foreach ($plans as $plan) {
            DataPlan::create($plan);
        }

        $this->command->info('Created ' . count($plans) . ' data plans');
    }
}
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