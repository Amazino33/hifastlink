<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RadAcct;
use App\Models\RadReply;
use Illuminate\Support\Facades\Log;

class KickExpiredUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:kick-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick users whose plan has expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get usernames with active sessions
        $activeUsernames = RadAcct::whereNull('acctstoptime')->pluck('username')->toArray();

        // Find users with expired plans and active sessions
        $expiredUsers = User::where('plan_expiry', '<', now())
            ->whereIn('username', $activeUsernames)
            ->get();

        foreach ($expiredUsers as $user) {
            $nextSubscription = $user->pendingSubscriptions()->first();

            if ($nextSubscription) {
                // Auto-renew from pending subscription queue
                $leftover = $user->calculateRolloverFor($nextSubscription->plan);

                $user->plan_id = $nextSubscription->plan_id;
                $user->data_limit = $nextSubscription->plan->data_limit + $leftover;
                $user->data_used = 0;
                $user->plan_expiry = now()->addDays($nextSubscription->plan->validity_days ?? 0);
                $user->plan_started_at = now();
                $user->is_family_admin = $nextSubscription->plan->is_family;
                $user->family_limit = $nextSubscription->plan->family_limit;
                $user->save(); // Triggers observer to sync RADIUS

                // Delete the processed subscription
                $nextSubscription->delete();

                Log::info("User {$user->username} auto-renewed from pending queue with {$leftover} bytes rollover.");
            } else {
                // Kick the user by setting data limit to 0
                RadReply::updateOrCreate(
                    [
                        'username' => $user->username,
                        'attribute' => 'Mikrotik-Total-Limit',
                    ],
                    [
                        'op' => ':=',
                        'value' => '0',
                    ]
                );

                // Move user back to default group
                try {
                    \App\Models\RadUserGroup::updateOrCreate(
                        ['username' => $user->username],
                        ['groupname' => 'default_group', 'priority' => 10]
                    );
                    Log::info("RadUserGroup set to default_group for expired user {$user->username}");
                } catch (\Exception $e) {
                    Log::error("Failed to set RadUserGroup for expired user {$user->username}: " . $e->getMessage());
                }

                // Update user status
                $user->connection_status = 'inactive';
                $user->save();

                // Log the action
                Log::info("Kicked expired user by setting data limit to 0: {$user->username}");
            }
        }

        $this->info('Expired users kicked: ' . $expiredUsers->count());
    }
}