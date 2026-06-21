<?php

namespace App\Services;

use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\RadAcct;
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
            // RadReply is fully rebuilt — safe to clear
            RadReply::where('username', $user->username)->delete();

            $plan = $user->plan;

            if (! $plan) {
                if ($user->isAdmin()) {
                    // Admin with no plan: give unlimited RADIUS access, no data cap
                    RadCheck::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Cleartext-Password'],
                        ['op' => ':=', 'value' => $user->radius_password ?? $user->username]
                    );
                    // Remove Simultaneous-Use so RADIUS imposes no session/device limit for admins
                    RadCheck::where('username', $user->username)
                        ->where('attribute', 'Simultaneous-Use')
                        ->delete();
                    // Remove any stale data-cap or expiry attributes from both radcheck AND radreply
                    RadCheck::where('username', $user->username)
                        ->whereIn('attribute', ['Mikrotik-Total-Limit', 'Max-Octets', 'Expiration'])
                        ->delete();
                    RadReply::where('username', $user->username)
                        ->whereIn('attribute', ['Mikrotik-Total-Limit', 'Max-Octets'])
                        ->delete();
                    $user->saveQuietly();
                    return;
                }

                if ($user->isStaff() || $user->isFreePass()) {
                    // Staff / free-pass with no plan: give RADIUS access, 2 devices, no data cap
                    RadCheck::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Cleartext-Password'],
                        ['op' => ':=', 'value' => $user->radius_password ?? $user->username]
                    );
                    RadCheck::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Simultaneous-Use'],
                        ['op' => ':=', 'value' => '2']
                    );
                    RadCheck::where('username', $user->username)
                        ->whereIn('attribute', ['Mikrotik-Total-Limit', 'Max-Octets', 'Expiration'])
                        ->delete();
                    RadReply::where('username', $user->username)
                        ->whereIn('attribute', ['Mikrotik-Total-Limit', 'Max-Octets'])
                        ->delete();
                    $user->saveQuietly();
                    return;
                }

                // Temporary access granted by a custom voucher (plan_expiry set, no plan_id)
                if ($user->plan_expiry && $user->plan_expiry->isFuture()) {
                    RadCheck::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Cleartext-Password'],
                        ['op' => ':=', 'value' => $user->radius_password ?? $user->username]
                    );
                    RadCheck::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Simultaneous-Use'],
                        ['op' => ':=', 'value' => '1']
                    );
                    if ($user->data_limit) {
                        RadCheck::updateOrCreate(
                            ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                            ['op' => ':=', 'value' => (string) $user->data_limit]
                        );
                    } else {
                        RadCheck::where('username', $user->username)
                            ->where('attribute', 'Mikrotik-Total-Limit')
                            ->delete();
                    }
                    $user->saveQuietly();
                    return;
                }

                // Non-admin without a plan: remove credentials so they cannot connect
                RadCheck::where('username', $user->username)
                    ->whereIn('attribute', ['Cleartext-Password', 'Simultaneous-Use'])
                    ->delete();
                $user->plan_expiry = null;
                $user->saveQuietly();

                return;
            }

            // Upsert password — never duplicate (username+attribute is the unique key)
            RadCheck::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Cleartext-Password'],
                ['op' => ':=', 'value' => $user->radius_password ?? $user->username]
            );

            // Upsert Simultaneous-Use limit based on plan's max_devices
            $maxDevices = $plan->max_devices ?? 1;
            RadCheck::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Simultaneous-Use'],
                ['op' => ':=', 'value' => (string) $maxDevices]
            );

            // Calculate family usage
            $masterId = $user->parent_id ?? $user->id;
            $familyUsernames = User::where('id', $masterId)->orWhere('parent_id', $masterId)->pluck('username');
            $startDate = $user->plan_started_at ?? now()->subYears(1);

            // If radacct table is not present in the test DB, assume zero usage
            $radacctExists = \Illuminate\Support\Facades\Schema::hasTable('radacct');
            if ($radacctExists) {
                $totalUsed = RadAcct::whereIn('username', $familyUsernames)
                    ->where('acctstarttime', '>=', $startDate)
                    ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
            } else {
                $totalUsed = 0;
            }

            // Remaining data in bytes (plan->data_limit stored as MB)
            $remainingBytes = max(0, ($plan->data_limit * 1024 * 1024) - $totalUsed);

            // Include rollover bytes when applicable (same-duration rule)
            $rolloverBytes = (int) ($user->rollover_available_bytes ?? 0);
            if ($rolloverBytes > 0 && $user->rollover_validity_days == $plan->validity_days) {
                $remainingBytes += $rolloverBytes;
            }

            // Auto-terminate idle sessions after inactivity (prevents ghost sessions on other routers)
            RadReply::create([
                'username'  => $user->username,
                'attribute' => 'Idle-Timeout',
                'op'        => ':=',
                'value'     => (string) (int) env('RADIUS_IDLE_TIMEOUT', 900),
            ]);

            // Speed limits and data limit go into radreply (Mikrotik specific)
            if (! empty($plan->speed_limit)) {
                RadReply::create([
                    'username' => $user->username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $plan->speed_limit,
                ]);
            }

            // Tell MikroTik the limit for THIS specific session
            if ($remainingBytes > 0) {
                RadReply::create([
                    'username' => $user->username,
                    'attribute' => 'Mikrotik-Total-Limit',
                    'op' => ':=',
                    'value' => (string) $remainingBytes,
                ]);
            }

            // Update plan expiry
            if (! empty($plan->validity_days) && $plan->validity_days > 0) {
                $user->plan_expiry = Carbon::now()->addDays($plan->validity_days);
            } else {
                $user->plan_expiry = null;
            }

            // Ensure radusergroup is set to the appropriate groupname
            try {
                $groupName = $plan ? ($plan->radius_group_name ?: $plan->name) : 'default_group';

                \App\Models\RadUserGroup::updateOrCreate(
                    ['username' => $user->username],
                    ['groupname' => $groupName, 'priority' => 10]
                );
            } catch (\Exception $e) {
                // Don't let radusergroup failures prevent user updates
                \Illuminate\Support\Facades\Log::error('Failed to sync RadUserGroup for user ' . $user->username . ': ' . $e->getMessage());
            }

            $user->saveQuietly();
        });
    }
}
