<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckDataLimits extends Command
{
    protected $signature = 'network:check-limits';
    protected $description = 'Check and enforce data limits for all users';

    public function handle()
    {
        $this->info('Checking data limits for all users...');

        $users = User::where('connection_status', 'active')->get();
        $suspended = 0;
        $notified = 0;

        foreach ($users as $user) {
            $usagePercent = ($user->data_used / $user->data_limit) * 100;

            if ($usagePercent >= 100) {
                // Suspend user
                $user->update(['connection_status' => 'suspended']);
                $this->warn("Suspended: {$user->username} (100% usage)");
                $suspended++;
            } elseif ($usagePercent >= 90) {
                // Send warning notification
                $this->sendDataWarning($user, $usagePercent);
                $this->info("Warning sent: {$user->username} ({$usagePercent}% usage)");
                $notified++;
            }
        }

        $this->info("Data limit check complete. Suspended: {$suspended}, Notified: {$notified}");

        // Sync changes to RADIUS
        if ($suspended > 0) {
            \Artisan::call('radius:sync-users');
        }
    }

    private function sendDataWarning(User $user, float $usagePercent)
    {
        // TODO: Implement email/SMS notification
        // For now, just log it
        \Log::info('Data usage warning', [
            'user' => $user->username,
            'usage_percent' => $usagePercent,
            'used' => $user->data_used,
            'limit' => $user->data_limit
        ]);
    }
}