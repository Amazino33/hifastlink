<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PlanSyncService;
use App\Models\RadReply;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Sync RADIUS for new users
        PlanSyncService::syncUserPlan($user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Sync RADIUS when plan, radius_password, or username changes
        if ($user->wasChanged('plan_id') || $user->wasChanged('radius_password') || $user->wasChanged('username')) {
            PlanSyncService::syncUserPlan($user);
        }
    }
}
