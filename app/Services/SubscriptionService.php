<?php

namespace App\Services;

use App\Models\RadAcct;
use App\Models\User;
use App\Models\Plan;
use App\Models\RadReply;
use App\Models\RadUserGroup;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\RadiusService;
use Illuminate\Support\Facades\Artisan;

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

            // Immediately disconnect active RADIUS sessions for this user
            try {
                $radius = new RadiusService();
                $radius->disconnectUser($user);
                // Also update devices table immediately
                Artisan::call('radius:sync-devices');
            } catch (\Exception $e) {
                Log::warning('Failed to force-disconnect user after expiry: ' . $e->getMessage());
            }

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

    /**
     * Determine whether the user (or their family master) may connect to a hotspot/router.
     *
     * Rules:
     * - User OR family master must have an active plan/subscription with remaining data or unlimited.
     * - Stored rollover bytes alone do NOT allow connection.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function canConnectToHotspot(User $user): bool
    {
        $masterId = $user->parent_id ?? $user->id;

        // If application uses a Subscription model, prefer that authoritative source
        if (class_exists(\App\Models\Subscription::class) && \Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
            // Check user's own active subscription
            $own = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->where(function ($q) {
                    $q->where('data_remaining', '>', 0)->orWhereNull('data_limit');
                })->exists();

            if ($own) return true;

            // Check family master (if different)
            if ($masterId !== $user->id) {
                $masterSub = \App\Models\Subscription::where('user_id', $masterId)
                    ->where('status', 'ACTIVE')
                    ->where('expires_at', '>', now())
                    ->where(function ($q) {
                        $q->where('data_remaining', '>', 0)->orWhereNull('data_limit');
                    })->exists();

                if ($masterSub) return true;
            }

            return false;
        }

        // Fallback: use User plan fields
        $masterUser = $masterId !== $user->id ? \App\Models\User::find($masterId) : $user;
        $checkUser  = $masterUser ?? $user;

        if (! ($checkUser->plan_expiry && $checkUser->plan_expiry->isFuture())) {
            return false;
        }

        // Unlimited data plan — time check already passed
        if (is_null($checkUser->data_limit)) {
            return true;
        }

        // Add voucher sessions to the stored data_used so the check is accurate
        $voucherCodes = \App\Models\Voucher::where('created_by', $checkUser->id)->pluck('code');
        $voucherUsed  = $voucherCodes->isNotEmpty()
            ? (int) \App\Models\RadAcct::whereIn('username', $voucherCodes)
                ->where('acctstarttime', '>=', $checkUser->plan_started_at ?? now()->subYears(1))
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'))
            : 0;

        $totalUsed = ($checkUser->data_used ?? 0) + $voucherUsed;

        if (max(0, $checkUser->data_limit - $totalUsed) > 0) {
            return true;
        }

        // Rollover-only does NOT permit connection here
        return false;
    }
}
