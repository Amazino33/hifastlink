<?php

namespace App\Observers;

use App\Models\User;
use App\Services\PlanSyncService;
use App\Models\RadReply;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Sync RADIUS when plan, radius_password, or username changes
        if ($user->wasChanged('plan_id') || $user->wasChanged('radius_password') || $user->wasChanged('username')) {
            PlanSyncService::syncUserPlan($user);
        }

        // Ensure Mikrotik speed limit (Mikrotik-Rate-Limit) is kept in radreply
        if ($user->wasChanged('plan_id') || $user->wasChanged('username')) {
            // Remove old entries for both previous and current username to be safe
            $oldUsername = $user->getOriginal('username') ?? null;
            if ($oldUsername) {
                RadReply::where('username', $oldUsername)
                    ->where('attribute', 'Mikrotik-Rate-Limit')
                    ->delete();
            }

            RadReply::where('username', $user->username)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->delete();

            // Insert the new speed limit if the user has a plan with a speed_limit defined
            if ($user->plan && ! empty($user->plan->speed_limit)) {
                $raw = trim($user->plan->speed_limit);

                // Normalize to MikroTik format if necessary (e.g. '512k' => '512k/512k', '1M' => '1M/1M')
                if (strpos($raw, '/') === false) {
                    // If it's numeric or numeric with suffix k/M, duplicate for upload/download
                    if (preg_match('/^\d+([kKmM])?$/', $raw)) {
                        $rateValue = $raw . '/' . $raw;
                    } else {
                        // Otherwise, use raw value as-is (user provided custom format)
                        $rateValue = $raw;
                    }
                } else {
                    $rateValue = $raw;
                }

                RadReply::create([
                    'username' => $user->username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $rateValue,
                ]);
            }
        }
    }
}
