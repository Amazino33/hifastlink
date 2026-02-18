<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionObserver
{
    /**
     * Handle the Subscription "creating" event.
     *
     * Fires BEFORE the INSERT — we normalise the User's family columns
     * so they reflect the new plan before any follow-up code runs.
     */
    public function creating(Subscription $subscription): void
    {
        $this->syncUserFamilyFields($subscription);
    }

    /**
     * Handle the Subscription "updating" event.
     *
     * Fires BEFORE the UPDATE — catches plan upgrades / downgrades /
     * swaps regardless of which controller or service triggered the save.
     */
    public function updating(Subscription $subscription): void
    {
        // Only act when plan_id is actually changing (or is being set for the first time).
        if ($subscription->isDirty('plan_id') || $subscription->isDirty('status')) {
            $this->syncUserFamilyFields($subscription);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Core logic: read the Plan attached to this subscription and stamp the
     * User record with the correct `family_limit` and `is_family_admin` values.
     *
     * This is the single source of truth — no controller needs to remember to
     * set these fields manually.
     */
    private function syncUserFamilyFields(Subscription $subscription): void
    {
        // Resolve plan — prefer the already-loaded relation to avoid an extra query.
        $plan = $subscription->relationLoaded('plan')
            ? $subscription->plan
            : Plan::find($subscription->plan_id);

        if (! $plan) {
            // No plan attached yet — nothing to enforce.
            return;
        }

        $userId = $subscription->user_id;

        if (! $userId) {
            return;
        }

        if ($plan->is_family) {
            // ✅ Family plan: grant admin privileges and set the seat limit.
            $familyLimit = $plan->family_limit ?? 0;

            User::where('id', $userId)->update([
                'is_family_admin' => true,
                'family_limit'    => $familyLimit,
            ]);

            Log::debug('SubscriptionObserver: family plan applied to user', [
                'user_id'      => $userId,
                'plan_id'      => $plan->id,
                'plan_name'    => $plan->name,
                'family_limit' => $familyLimit,
            ]);
        } else {
            // ❌ Non-family plan: strip family privileges unconditionally.
            // This is the "safety net" — prevents NULL / stale values.
            User::where('id', $userId)->update([
                'is_family_admin' => false,
                'family_limit'    => 0,
            ]);

            Log::debug('SubscriptionObserver: non-family plan — family fields reset on user', [
                'user_id'   => $userId,
                'plan_id'   => $plan->id,
                'plan_name' => $plan->name,
            ]);
        }
    }
}
