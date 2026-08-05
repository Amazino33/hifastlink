<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class FreeTrialPlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(
            ['name' => 'Free Trial'],
            [
                'price'          => 0,
                'data_limit'     => null,   // unlimited
                'limit_unit'     => 'Unlimited',
                'validity_days'  => 2,
                'max_devices'    => 1,
                'is_active'      => true,
                'is_admin_only'  => true,   // hidden from shop
                'description'    => '2-day free trial. One claim per phone number.',
            ]
        );

        $this->command->info('Free Trial plan ready.');
    }
}
