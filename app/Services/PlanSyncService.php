<?php

namespace App\Services;

use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadReply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PlanSyncService
{
    /**
     * Sync user plan limits to RADIUS tables (radcheck/radreply).
     */
    public static function syncUserPlan(User $user): void
    {
        if (! $user->username) {
            return; // nothing to do without username
        }

        DB::transaction(function () use ($user) {
            // Clear previous records for this username
            RadCheck::where('username', $user->username)->delete();
            RadReply::where('username', $user->username)->delete();

            $plan = $user->plan;

            if (! $plan) {
                // No plan assigned: clear expiry and finish
                $user->plan_expiry = null;
                $user->saveQuietly();

                return;
            }

            // Always add Cleartext-Password so the user can authenticate.
            RadCheck::create([
                'username' => $user->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $user->radius_password ?? $user->username,
            ]);

            // Data limit (bytes)
            if (! empty($plan->data_limit) && $plan->data_limit > 0) {
                RadCheck::create([
                    'username' => $user->username,
                    'attribute' => 'Max-Total-Octets',
                    'op' => ':=',
                    'value' => (string) $plan->data_limit,
                ]);
            }

            // Session time limit
            if (! empty($plan->time_limit) && $plan->time_limit > 0) {
                RadCheck::create([
                    'username' => $user->username,
                    'attribute' => 'Max-All-Session-Time',
                    'op' => ':=',
                    'value' => (string) $plan->time_limit,
                ]);
            }

            // Speed limits go into radreply (Mikrotik specific)
            if (! empty($plan->speed_limit_download)) {
                $upload = $plan->speed_limit_upload ?? 0;
                $download = $plan->speed_limit_download;

                RadReply::create([
                    'username' => $user->username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => (string) $upload . 'k/' . (string) $download . 'k',
                ]);
            }

            // Update plan expiry
            if (! empty($plan->validity_days) && $plan->validity_days > 0) {
                $user->plan_expiry = Carbon::now()->addDays($plan->validity_days);
            } else {
                $user->plan_expiry = null;
            }

            $user->saveQuietly();
        });
    }
}
