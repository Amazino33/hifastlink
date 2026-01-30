<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PlanSyncService;

class UserPlanObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('plan_id')) {
            // Defer actual implementation to PlanSyncService.
            PlanSyncService::syncUserPlan($user);
        }
    }
}
