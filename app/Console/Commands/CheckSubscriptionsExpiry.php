<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionsExpiry extends Command
{
    protected $signature = 'subscriptions:check-expiry';

    protected $description = 'Check for subscriptions that have expired and handle rollover/expiration actions';

    public function handle()
    {
        $subscriptionService = new SubscriptionService();

        $users = User::whereNotNull('plan_expiry')
            ->where('plan_expiry', '<=', now())
            ->get();

        foreach ($users as $user) {
            $nextSubscription = $user->pendingSubscriptions()->first();

            if ($nextSubscription) {
                // Auto-apply the pending plan using the existing logic
                $leftover = $user->calculateRolloverFor($nextSubscription->plan);

                $user->plan_id = $nextSubscription->plan_id;
                $user->data_limit = $nextSubscription->plan->data_limit + $leftover;
                $user->data_used = 0;
                $user->plan_expiry = now()->addDays($nextSubscription->plan->validity_days ?? 0);
                $user->plan_started_at = now();
                $user->is_family_admin = $nextSubscription->plan->is_family;
                $user->family_limit = $nextSubscription->plan->family_limit;
                $user->save();

                $nextSubscription->delete();

                Log::info("User {$user->username} auto-renewed from pending queue with {$leftover} bytes rollover.");
            } else {
                try {
                    $subscriptionService->expireForExpiry($user);
                } catch (\Exception $e) {
                    Log::error("Failed to expire subscription for {$user->username}: " . $e->getMessage());
                }
            }
        }

        $this->info('Checked and processed expired subscriptions: ' . $users->count());
    }
}
