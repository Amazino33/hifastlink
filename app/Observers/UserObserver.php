<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PlanSyncService;
use App\Models\RadReply;
use App\Models\RadCheck;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Sync RADIUS for new users
        PlanSyncService::syncUserPlan($user);

        // Sync Login-Time restriction
        if ($user->plan && ! empty($user->plan->allowed_login_time)) {
            RadCheck::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Login-Time'],
                ['op' => ':=', 'value' => $user->plan->allowed_login_time]
            );
        } else {
            RadCheck::where('username', $user->username)->where('attribute', 'Login-Time')->delete();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // When plan_id is cleared without an explicit plan_expiry update in the same save,
        // the expiry and data_limit are stale leftovers from the old plan. Wipe them so
        // canConnectToHotspot() cannot grant access based on phantom values.
        // Voucher redemptions always change plan_expiry in the same save, so wasChanged('plan_expiry')
        // is true there and this block is skipped correctly.
        if ($user->wasChanged('plan_id') && is_null($user->plan_id)
            && ! $user->wasChanged('plan_expiry')
            && ! $user->wasChanged('plan_started_at')
        ) {
            $user->plan_expiry = null;
            $user->data_limit  = null;
            $user->saveQuietly();
        }

        // Sync RADIUS when plan, radius_password, or username changes
        if ($user->wasChanged('plan_id') || $user->wasChanged('radius_password') || $user->wasChanged('username')) {
            PlanSyncService::syncUserPlan($user);

            // Sync Login-Time restriction
            if ($user->plan && ! empty($user->plan->allowed_login_time)) {
                RadCheck::updateOrCreate(
                    ['username' => $user->username, 'attribute' => 'Login-Time'],
                    ['op' => ':=', 'value' => $user->plan->allowed_login_time]
                );
            } else {
                RadCheck::where('username', $user->username)->where('attribute', 'Login-Time')->delete();
            }
        }
    }
}
