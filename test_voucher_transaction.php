<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Voucher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Simulate voucher redemption
echo "=== Simulating Voucher Redemption ===\n";

// Find an unused voucher
$voucher = Voucher::where('is_used', false)->first();
if (!$voucher) {
    echo "No unused vouchers found!\n";
    exit(1);
}

echo "Found voucher: {$voucher->code}\n";

// Find a user
$user = User::first();
if (!$user) {
    echo "No users found!\n";
    exit(1);
}

echo "Using user: {$user->username} (ID: {$user->id})\n";

// Simulate authentication
Auth::login($user);

// Get the plan
$newPlan = $voucher->plan;
if (!$newPlan) {
    echo "Voucher has no associated plan!\n";
    exit(1);
}

echo "Plan: {$newPlan->name} (ID: {$newPlan->id})\n";

// Simulate the transaction creation logic from redeemVoucher
try {
    echo "Attempting to create transaction...\n";

    \Illuminate\Support\Facades\Log::info("Attempting to create transaction for voucher {$voucher->code}", [
        'user_id' => $user->id,
        'plan_id' => $newPlan->id,
        'amount' => $newPlan->price,
        'reference' => 'VCH-' . $voucher->code,
        'status' => 'success',
        'gateway' => 'voucher',
        'paid_at' => now(),
    ]);

    $transaction = \App\Models\Transaction::create([
        'user_id' => $user->id,
        'plan_id' => $newPlan->id,
        'amount' => $newPlan->price,
        'reference' => 'VCH-' . $voucher->code,
        'status' => 'success',
        'gateway' => 'voucher',
        'paid_at' => now(),
    ]);

    echo "Transaction created successfully with ID: {$transaction->id}\n";
    \Illuminate\Support\Facades\Log::info("Transaction created successfully for voucher {$voucher->code} with ID: {$transaction->id}");

} catch (\Exception $e) {
    echo "Failed to create transaction: " . $e->getMessage() . "\n";
    \Illuminate\Support\Facades\Log::error("Failed to create transaction for voucher {$voucher->code}: " . $e->getMessage(), [
        'exception' => $e,
        'user_id' => $user->id,
        'plan_id' => $newPlan->id,
        'plan_exists' => \App\Models\Plan::find($newPlan->id) ? 'yes' : 'no',
        'user_exists' => \App\Models\User::find($user->id) ? 'yes' : 'no',
    ]);
}

echo "=== Test Complete ===\n";