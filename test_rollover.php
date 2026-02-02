<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Plan;

// Test the new rollover logic
echo "=== Testing Strict Rollover Rules ===\n";

// Create test plans with different validity periods
$plan30Days = Plan::firstOrCreate([
    'name' => 'Test 30 Days',
    'validity_days' => 30,
    'data_limit' => 1000000000, // 1GB
    'price' => 5000,
]);

$plan7Days = Plan::firstOrCreate([
    'name' => 'Test 7 Days',
    'validity_days' => 7,
    'data_limit' => 500000000, // 500MB
    'price' => 2000,
]);

$plan30DaysAlt = Plan::firstOrCreate([
    'name' => 'Test 30 Days Alt',
    'validity_days' => 30,
    'data_limit' => 2000000000, // 2GB
    'price' => 8000,
]);

// Find an existing user or create a new one
$user = User::where('email', 'test-rollover@example.com')->first();

if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test-rollover@example.com',
        'username' => 'testuser_rollover_' . time(),
        'password' => bcrypt('password'),
        'plan_id' => $plan30Days->id,
        'data_limit' => 1000000000,
        'data_used' => 200000000, // Used 200MB, so 800MB remaining
    ]);
} else {
    // Update existing user for testing
    $user->update([
        'plan_id' => $plan30Days->id,
        'data_limit' => 1000000000,
        'data_used' => 200000000,
    ]);
}

echo "Test User Setup:\n";
echo "- Current Plan: {$user->plan->name} ({$user->plan->validity_days} days)\n";
echo "- Data Limit: " . number_format($user->data_limit) . " bytes\n";
echo "- Data Used: " . number_format($user->data_used) . " bytes\n";
echo "- Remaining: " . number_format($user->data_limit - $user->data_used) . " bytes\n\n";

echo "Testing Rollover Scenarios:\n";

// Test 1: Same validity (30 days) - should rollover
$rollover1 = $user->calculateRolloverFor($plan30DaysAlt);
echo "1. Switching to another 30-day plan: Rollover = " . number_format($rollover1) . " bytes\n";

// Test 2: Different validity (7 days) - should NOT rollover
$rollover2 = $user->calculateRolloverFor($plan7Days);
echo "2. Switching to 7-day plan: Rollover = " . number_format($rollover2) . " bytes\n";

// Test 3: Same validity again (30 days) - should rollover
$rollover3 = $user->calculateRolloverFor($plan30Days);
echo "3. Switching back to 30-day plan: Rollover = " . number_format($rollover3) . " bytes\n";

echo "\n=== Test Complete ===\n";
echo "Expected: Scenarios 1 and 3 should have rollover, Scenario 2 should have 0 rollover\n";