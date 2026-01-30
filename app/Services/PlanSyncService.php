<?php

namespace App\Services;

use App\Models\User;

class PlanSyncService
{
    /**
     * Sync user plan limits to RADIUS tables (radcheck/radreply).
     * Implementation to be done later; this is a stub for now.
     */
    public static function syncUserPlan(User $user): void
    {
        // TODO: Implement syncing logic to radcheck/radreply.
    }
}
