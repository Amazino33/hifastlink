<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigratePaymentsToTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-payments-to-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing payments to transactions table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of payments to transactions...');

        $payments = \App\Models\Payment::all();
        $migrated = 0;

        foreach ($payments as $payment) {
            // Check if transaction already exists for this payment
            $existingTransaction = \App\Models\Transaction::where('reference', $payment->reference)->first();

            if (!$existingTransaction) {
                // Find the plan by name
                $plan = \App\Models\Plan::where('name', $payment->plan_name)->first();

                \App\Models\Transaction::create([
                    'user_id' => $payment->user_id,
                    'plan_id' => $plan ? $plan->id : null,
                    'amount' => $payment->amount,
                    'reference' => $payment->reference,
                    'status' => 'success',
                    'gateway' => 'paystack', // Assuming payments are from paystack
                    'paid_at' => $payment->created_at,
                ]);

                $migrated++;
            }
        }

        $this->info("Migration completed! Migrated {$migrated} payments to transactions.");
    }
}
