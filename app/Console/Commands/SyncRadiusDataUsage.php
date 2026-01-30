<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RadiusService;
use Illuminate\Console\Command;

class SyncRadiusDataUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radius:sync-data-usage {--user=* : Specific user IDs to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user data usage from RADIUS accounting database';

    protected RadiusService $radiusService;

    /**
     * Create a new command instance.
     */
    public function __construct(RadiusService $radiusService)
    {
        parent::__construct();
        $this->radiusService = $radiusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIds = $this->option('user');

        if (!empty($userIds)) {
            // Sync specific users
            $users = User::whereIn('id', $userIds)->get();
            $this->info("Syncing data usage for " . $users->count() . " specific user(s)...");
        } else {
            // Sync all active users
            $users = User::whereNotNull('username')
                ->where(function ($query) {
                    $query->whereNull('subscription_end_date')
                        ->orWhere('subscription_end_date', '>', now());
                })
                ->get();
            $this->info("Syncing data usage for all active users (" . $users->count() . " users)...");
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($users as $user) {
            try {
                // Sync data usage
                $this->radiusService->syncUserDataUsage($user);
                
                // Update connection status
                $this->radiusService->updateConnectionStatus($user);

                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nFailed to sync user {$user->username}: " . $e->getMessage());
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Sync completed!");
        $this->info("✓ Success: {$successCount}");
        
        if ($failCount > 0) {
            $this->warn("✗ Failed: {$failCount}");
        }

        return Command::SUCCESS;
    }
}
