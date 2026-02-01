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
            if ($user->pending_plan_id && $user->pendingPlan) {
                // Auto-renew from pending plan
                $leftover = max(0, $user->data_limit - $user->data_used);

                $user->plan_id = $user->pending_plan_id;
                $user->data_limit = $user->pendingPlan->data_limit + $leftover;
                $user->data_used = 0;
                $user->plan_expiry = now()->addDays($user->pendingPlan->validity_days ?? 0);
                $user->plan_started_at = now();
                $user->is_family_admin = $user->pendingPlan->is_family;
                $user->family_limit = $user->pendingPlan->family_limit;
                $user->pending_plan_id = null;
                $user->pending_plan_purchased_at = null;
                $user->save(); // Triggers observer to sync RADIUS

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