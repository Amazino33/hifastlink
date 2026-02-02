<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Voucher;
use App\Models\Transaction;

// Check the specific voucher that was redeemed
$voucher = Voucher::where('code', 'BFH0-1455')->first();

if (!$voucher) {
    echo "Voucher BFH0-1455 not found!\n";
    exit(1);
}

echo "Voucher Details:\n";
echo "ID: {$voucher->id}\n";
echo "Code: {$voucher->code}\n";
echo "Plan ID: {$voucher->plan_id}\n";
echo "Is Used: " . ($voucher->is_used ? 'Yes' : 'No') . "\n";
echo "Used By: {$voucher->used_by}\n";
echo "Used At: {$voucher->used_at}\n";

$plan = $voucher->plan;
echo "Plan: " . ($plan ? $plan->name . " (ID: {$plan->id})" : 'NOT FOUND') . "\n";

$transaction = Transaction::where('reference', 'VCH-' . $voucher->code)->first();
echo "Transaction: " . ($transaction ? "Found (ID: {$transaction->id})" : 'NOT FOUND') . "\n";

if ($transaction) {
    echo "Transaction Details:\n";
    echo "User ID: {$transaction->user_id}\n";
    echo "Plan ID: {$transaction->plan_id}\n";
    echo "Amount: {$transaction->amount}\n";
    echo "Gateway: {$transaction->gateway}\n";
} else {
    echo "Creating missing transaction...\n";
    try {
        $transaction = Transaction::create([
            'user_id' => $voucher->used_by,
            'plan_id' => $voucher->plan_id,
            'amount' => $plan->price,
            'reference' => 'VCH-' . $voucher->code,
            'status' => 'success',
            'gateway' => 'voucher',
            'paid_at' => $voucher->used_at,
        ]);
        echo "Transaction created with ID: {$transaction->id}\n";
    } catch (\Exception $e) {
        echo "Failed to create transaction: " . $e->getMessage() . "\n";
    }
}