<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaction;
use App\Models\Voucher;
use App\Models\Payment;

echo "=== Transaction Check ===\n";

$count = Transaction::count();
echo "Total transactions: {$count}\n";

if ($count > 0) {
    echo "Latest 5 transactions:\n";
    $transactions = Transaction::latest()->take(5)->get();
    foreach ($transactions as $transaction) {
        echo "ID: {$transaction->id} | Ref: {$transaction->reference} | Amount: {$transaction->amount} | Gateway: {$transaction->gateway} | User: {$transaction->user_id}\n";
    }
}

echo "\n=== Payments Check ===\n";
$paymentCount = Payment::count();
echo "Total payments: {$paymentCount}\n";

if ($paymentCount > 0) {
    echo "Latest 3 payments:\n";
    $payments = Payment::latest()->take(3)->get();
    foreach ($payments as $payment) {
        echo "ID: {$payment->id} | Ref: {$payment->reference} | Amount: {$payment->amount} | User: {$payment->user_id}\n";
    }
}

echo "\n=== Vouchers Check ===\n";
$voucherCount = Voucher::count();
$usedVouchers = Voucher::where('is_used', true)->count();
echo "Total vouchers: {$voucherCount} | Used: {$usedVouchers}\n";

if ($usedVouchers > 0) {
    echo "Latest 3 used vouchers:\n";
    $vouchers = Voucher::where('is_used', true)->latest('used_at')->take(3)->get();
    foreach ($vouchers as $voucher) {
        $plan = $voucher->plan;
        echo "ID: {$voucher->id} | Code: {$voucher->code} | Plan: " . ($plan ? $plan->name : 'N/A') . " | Used by: {$voucher->used_by} | At: {$voucher->used_at}\n";
    }
}

echo "\n=== Detailed Voucher vs Transaction Check ===\n";
$usedVouchers = Voucher::where('is_used', true)->with('plan')->get();
foreach ($usedVouchers as $voucher) {
    $transaction = Transaction::where('reference', 'VCH-' . $voucher->code)->first();
    $status = $transaction ? "HAS TRANSACTION (ID: {$transaction->id})" : "MISSING TRANSACTION";
    echo "Voucher: {$voucher->code} | Used by: {$voucher->used_by} | {$status}\n";
}