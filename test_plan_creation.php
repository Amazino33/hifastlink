<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Console\Command;
use App\Models\Plan;

// Test plan creation with the fixed data
$planData = [
    'name' => 'Test Custom Plan',
    'description' => 'A test plan to verify database constraints',
    'price' => 100,
    'data_limit' => 100 * 1048576, // 100 MB in bytes
    'time_limit' => null,
    'speed_limit_upload' => null,
    'speed_limit_download' => null,
    'validity_days' => 30,
    'speed_limit' => '10M/10M', // Should not be null
    'allowed_login_time' => null,
    'limit_unit' => 'MB',
    'max_devices' => null,
    'features' => null,
    'is_active' => true,
    'is_featured' => false,
    'sort_order' => 0,
    'router_id' => 1, // Assuming router ID 1 exists
    'is_custom' => true,
    'show_universal_plans' => false,
];

try {
    $plan = Plan::create($planData);
    echo "Plan created successfully with ID: " . $plan->id . "\n";
    echo "Speed limit: " . $plan->speed_limit . "\n";
} catch (Exception $e) {
    echo "Error creating plan: " . $e->getMessage() . "\n";
}