<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestSoftDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-soft-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test soft delete functionality for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing soft delete functionality...');

        // Find a user to test with (avoid the main admin user)
        $user = User::where('id', '>', 1)->first();

        if (!$user) {
            $this->warn('No test user found');
            return;
        }

        $this->info("Testing with user: {$user->name} (ID: {$user->id})");

        // Check payment count before deletion
        $paymentCount = $user->payments()->count();
        $this->info("User has {$paymentCount} payment records");

        // Perform soft delete
        $user->delete();
        $this->info('Soft delete performed successfully!');

        // Check if user is still in database but marked as deleted
        $deletedUser = User::withTrashed()->find($user->id);
        if ($deletedUser && $deletedUser->trashed()) {
            $this->info('✓ User is soft deleted (trashed)');
        } else {
            $this->error('✗ User is not properly soft deleted');
        }

        // Check if payment records are still intact
        $deletedPaymentCount = $deletedUser->payments()->count();
        $this->info("Payment records after deletion: {$deletedPaymentCount}");

        if ($paymentCount === $deletedPaymentCount) {
            $this->info('✓ Payment records preserved!');
        } else {
            $this->error('✗ Payment records lost!');
        }

        // Restore the user for future testing
        $deletedUser->restore();
        $this->info('User restored for future testing');

        $this->info('Test completed successfully!');
    }
}