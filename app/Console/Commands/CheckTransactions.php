<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check transaction data in database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = \App\Models\Transaction::count();
        $this->info("Total transactions: {$count}");

        if ($count > 0) {
            $this->info("Latest 5 transactions:");
            $transactions = \App\Models\Transaction::latest()->take(5)->get();
            foreach ($transactions as $transaction) {
                $this->line("ID: {$transaction->id} | Ref: {$transaction->reference} | Amount: {$transaction->amount} | Gateway: {$transaction->gateway} | User: {$transaction->user_id}");
            }
        }

        // Also check payments
        $paymentCount = \App\Models\Payment::count();
        $this->info("Total payments: {$paymentCount}");

        if ($paymentCount > 0) {
            $this->info("Latest 3 payments:");
            $payments = \App\Models\Payment::latest()->take(3)->get();
            foreach ($payments as $payment) {
                $this->line("ID: {$payment->id} | Ref: {$payment->reference} | Amount: {$payment->amount} | User: {$payment->user_id}");
            }
        }

        // Check vouchers
        $voucherCount = \App\Models\Voucher::count();
        $usedVouchers = \App\Models\Voucher::where('is_used', true)->count();
        $this->info("Total vouchers: {$voucherCount} | Used: {$usedVouchers}");

        if ($usedVouchers > 0) {
            $this->info("Latest 3 used vouchers:");
            $vouchers = \App\Models\Voucher::where('is_used', true)->latest('used_at')->take(3)->get();
            foreach ($vouchers as $voucher) {
                $plan = $voucher->plan;
                $this->line("ID: {$voucher->id} | Code: {$voucher->code} | Plan: " . ($plan ? $plan->name : 'N/A') . " | Used by: {$voucher->used_by} | At: {$voucher->used_at}");
            }
        }

        // Check for transaction creation issues
        $this->info("Checking for transaction creation issues...");
        $voucherTransactionCount = \App\Models\Transaction::where('gateway', 'voucher')->count();
        $this->info("Voucher transactions: {$voucherTransactionCount}");

        if ($usedVouchers > $voucherTransactionCount) {
            $missing = $usedVouchers - $voucherTransactionCount;
            $this->warn("{$missing} voucher redemptions are missing transaction records!");
        }
    }
}
