<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\RadCheck;
use Illuminate\Console\Command;

class SyncSimultaneousUse extends Command
{
    protected $signature = 'radius:sync-simultaneous-use';
    protected $description = 'Sync Simultaneous-Use attribute for all users in RadCheck';

    public function handle()
    {
        $this->info('Syncing Simultaneous-Use attribute for all users...');

        $users = User::whereNotNull('username')->with('plan')->get();
        $updated = 0;
        $created = 0;

        foreach ($users as $user) {
            $maxDevices = ($user->plan && $user->plan->max_devices) ? $user->plan->max_devices : 1;

            $result = RadCheck::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Simultaneous-Use'],
                ['op' => ':=', 'value' => (string) $maxDevices]
            );

            if ($result->wasRecentlyCreated) {
                $created++;
                $this->line("Created {$user->username}: {$maxDevices} device(s)");
            } else {
                $updated++;
                $this->line("Updated {$user->username}: {$maxDevices} device(s)");
            }
        }

        $this->newLine();
        $this->info("Sync complete!");
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Total processed: " . ($created + $updated));

        return Command::SUCCESS;
    }
}
