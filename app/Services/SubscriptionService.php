<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\RadReply;
use App\Models\RadUserGroup;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Expire a user's subscription due to time expiry and snapshot rollover bytes.
     */
    public function expireForExpiry(User $user): void
    {
        try {
            $remaining = 0;
            if ($user->data_limit && $user->data_used < $user->data_limit) {
                $remaining = max(0, $user->data_limit - $user->data_used);
            }

            // Snapshot rollover and validity days (if a plan was present)
            $user->rollover_available_bytes = $remaining;
            $user->rollover_validity_days = $user->plan ? $user->plan->validity_days : null;

            // Clear plan assignment
            $user->plan_id = null;
            $user->plan_expiry = null;

            // Persist
            $user->save();

            // Ensure Mikrotik enforces 0 limit
            RadReply::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                ['op' => ':=', 'value' => '0']
            );

            // Move user back to default group
            RadUserGroup::updateOrCreate(
                ['username' => $user->username],
                ['groupname' => 'default_group', 'priority' => 10]
            );

            $user->connection_status = 'inactive';
            $user->save();

            Log::info("Expired subscription for {$user->username}, rollover={$remaining} bytes");
        } catch (\Exception $e) {
            Log::error('Failed to expire subscription for user ' . $user->username . ': ' . $e->getMessage());
        }
    }

    /**
     * Expire a user's subscription because it was exhausted (no rollover).
     */
    public function expireForExhaustion(User $user): void
    {
        try {
            // No rollover on exhaustion
            $user->rollover_available_bytes = 0;
            $user->rollover_validity_days = null;

            // Clear plan
            $user->plan_id = null;
            $user->plan_expiry = null;
            $user->connection_status = 'exhausted';
            $user->save();

            RadReply::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                ['op' => ':=', 'value' => '0']
            );

            RadUserGroup::updateOrCreate(
                ['username' => $user->username],
                ['groupname' => 'default_group', 'priority' => 10]
            );

            Log::info("Expired exhausted subscription for {$user->username} - rollover cleared");
        } catch (\Exception $e) {
            Log::error('Failed to expire exhausted subscription for user ' . $user->username . ': ' . $e->getMessage());
        }
    }

    /**
     * Consume stored rollover (if validity days match new plan) and return bytes applied.
     * This will clear the rollover fields on the user when applied.
     */
    public function consumeRolloverOnPurchase(User $user, Plan $plan): int
    {
        if ($user->rollover_available_bytes && $user->rollover_validity_days == $plan->validity_days) {
            $bytes = (int) $user->rollover_available_bytes;

            $user->rollover_available_bytes = 0;
            $user->rollover_validity_days = null;
            $user->save();

            Log::info("Applied {$bytes} bytes of rollover to new plan for user {$user->username}");

            return $bytes;
        }

        return 0;
    }
}
