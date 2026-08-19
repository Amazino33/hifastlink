<?php

namespace App\Services;

use App\Models\AppSetting;
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

        // Admins always get unrestricted RADIUS access regardless of plan assignment.
        // A plan on the admin account is cosmetic only — never apply its speed or data caps.
        if ($user->isAdmin()) {
            DB::transaction(function () use ($user) {
                RadCheck::updateOrCreate(
                    ['username' => $user->username, 'attribute' => 'Cleartext-Password'],
                    ['op' => ':=', 'value' => $user->radius_password ?? $user->username]
                );
                RadCheck::where('username', $user->username)
                    ->whereIn('attribute', ['Simultaneous-Use', 'Mikrotik-Total-Limit', 'Max-Octets', 'Expiration'])
                    ->delete();
                RadReply::where('username', $user->username)->delete();
                // Remove any group assignment — radgroupreply entries on that group
                // would otherwise still apply rate limits even with radreply empty.
                try {
                    \App\Models\RadUserGroup::where('username', $user->username)->delete();
                } catch (\Exception $e) {
                    // radusergroup may not exist on all environments
                }
                $user->saveQuietly();
            });
            return;
        }

        DB::transaction(function () use ($user) {
            // RadReply is fully rebuilt — safe to clear
            RadReply::where('username', $user->username)->delete();

            $plan = $user->plan;

            if (! $plan) {

                if ($user->isFreePass()) {
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
                    // Apply global speed cap if configured
                    $globalRate = self::globalRateLimit();
                    if ($globalRate) {
                        RadReply::create([
                            'username'  => $user->username,
                            'attribute' => 'Mikrotik-Rate-Limit',
                            'op'        => ':=',
                            'value'     => $globalRate,
                        ]);
                    }
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
                    // Apply global speed cap for voucher-access users
                    $globalRate = self::globalRateLimit();
                    if ($globalRate) {
                        RadReply::create([
                            'username'  => $user->username,
                            'attribute' => 'Mikrotik-Rate-Limit',
                            'op'        => ':=',
                            'value'     => $globalRate,
                        ]);
                    }
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

            // Remaining data in bytes — respect the plan's limit_unit (MB or GB)
            $planLimitBytes = $plan->limit_unit === 'GB'
                ? (int) ($plan->data_limit * 1073741824)
                : (int) ($plan->data_limit * 1048576);
            $remainingBytes = max(0, $planLimitBytes - $totalUsed);

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

            // Speed limit: plan-specific wins; global cap applies as fallback
            $planRateLimit = $plan->speed_limit;
            if (! empty($planRateLimit)) {
                RadReply::create([
                    'username'  => $user->username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op'        => ':=',
                    'value'     => $planRateLimit,
                ]);
            } else {
                $globalRate = self::globalRateLimit();
                if ($globalRate) {
                    RadReply::create([
                        'username'  => $user->username,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op'        => ':=',
                        'value'     => $globalRate,
                    ]);
                }
            }

            // Tell MikroTik the remaining data for this session.
            // Mikrotik-Total-Limit is a 32-bit attribute (max ~4 GB). For larger values
            // we split into Gigawords (each unit = 4,294,967,296 bytes) + the low remainder.
            if ($remainingBytes > 0) {
                $gigawords  = (int) ($remainingBytes / 4294967296);
                $totalLimit = $remainingBytes - ($gigawords * 4294967296);
                RadReply::create([
                    'username'  => $user->username,
                    'attribute' => 'Mikrotik-Total-Limit',
                    'op'        => ':=',
                    'value'     => (string) $totalLimit,
                ]);
                if ($gigawords > 0) {
                    RadReply::create([
                        'username'  => $user->username,
                        'attribute' => 'Mikrotik-Total-Limit-Gigawords',
                        'op'        => ':=',
                        'value'     => (string) $gigawords,
                    ]);
                }
            }

            // Unlimited-data plans have no Mikrotik-Total-Limit to end the session,
            // so write Session-Timeout instead. Without it, a long keepalive-timeout
            // keeps the session alive past plan expiry — Expiration only blocks new
            // logins, not already-running sessions.
            if (!$plan->data_limit && !empty($plan->validity_days) && $plan->validity_days > 0) {
                $planExpiry = $user->plan_expiry
                    ?? Carbon::now()->addDays($plan->validity_days);
                $secondsRemaining = (int) now()->diffInSeconds(Carbon::parse($planExpiry), false);
                if ($secondsRemaining > 0) {
                    RadReply::create([
                        'username'  => $user->username,
                        'attribute' => 'Session-Timeout',
                        'op'        => ':=',
                        'value'     => (string) $secondsRemaining,
                    ]);
                }
            }

            // Update plan expiry.
            // If the caller (e.g. FreeTrialService) already wrote a future expiry onto the
            // model before triggering this sync, keep it — don't recalculate from validity_days,
            // which could be 0/null for an open-ended trial plan and would null out the expiry.
            $alreadyHasFutureExpiry = $user->plan_expiry
                && Carbon::parse($user->plan_expiry)->isFuture();

            if (! $alreadyHasFutureExpiry) {
                if (! empty($plan->validity_days) && $plan->validity_days > 0) {
                    $user->plan_expiry = Carbon::now()->addDays($plan->validity_days);
                } else {
                    $user->plan_expiry = null;
                }
            }

            // RADIUS-enforced expiry: reject any login - including a silent
            // mac-cookie reconnect - once the plan lapses. FreeRADIUS's
            // expiration module reads this live, so it self-enforces the
            // moment plan_expiry passes, without waiting for the expiry cron.
            if ($user->plan_expiry) {
                RadCheck::updateOrCreate(
                    ['username' => $user->username, 'attribute' => 'Expiration'],
                    ['op' => ':=', 'value' => Carbon::parse($user->plan_expiry)->format('d M Y H:i')]
                );
            } else {
                // Unlimited-duration plan -> no time-based expiry
                RadCheck::where('username', $user->username)
                    ->where('attribute', 'Expiration')
                    ->delete();
            }

            // Propagate the new plan expiry to all voucher RADIUS entries this creator owns,
            // so already-activated vouchers start working again after a renewal instead of
            // being blocked by a stale Expiration from the previous plan cycle.
            $voucherRadNames = \App\Models\Voucher::where('created_by', $user->id)
                ->pluck('code')
                ->map(fn ($c) => 'vch_' . strtolower($c));

            if ($voucherRadNames->isNotEmpty()) {
                if ($user->plan_expiry) {
                    $expiryStr = Carbon::parse($user->plan_expiry)->format('d M Y H:i');
                    RadCheck::whereIn('username', $voucherRadNames)
                        ->where('attribute', 'Expiration')
                        ->update(['value' => $expiryStr]);
                } else {
                    RadCheck::whereIn('username', $voucherRadNames)
                        ->where('attribute', 'Expiration')
                        ->delete();
                }
            }

            // Propagate plan expiry to managed sub-accounts (router owner's customer accounts).
            // Their credentials persist across renewals — update Expiration in radcheck AND
            // plan_expiry in the users table so syncUserPlan stays correct if called for them.
            $subAccounts = User::where('parent_id', $user->id)->get(['id', 'username']);
            if ($subAccounts->isNotEmpty()) {
                $subUsernames = $subAccounts->pluck('username')->filter();
                if ($user->plan_expiry) {
                    $expiryStr = Carbon::parse($user->plan_expiry)->format('d M Y H:i');
                    RadCheck::whereIn('username', $subUsernames)
                        ->where('attribute', 'Expiration')
                        ->update(['value' => $expiryStr]);
                    User::whereIn('id', $subAccounts->pluck('id'))
                        ->update(['plan_expiry' => $user->plan_expiry]);
                } else {
                    RadCheck::whereIn('username', $subUsernames)
                        ->where('attribute', 'Expiration')
                        ->delete();
                    User::whereIn('id', $subAccounts->pluck('id'))
                        ->update(['plan_expiry' => null]);
                }
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

    /**
     * Returns the global fallback rate-limit string (e.g. "1024k/2048k") or null if disabled.
     * Used when a plan has no per-plan speed set, so no single user can hog all bandwidth.
     */
    private static function globalRateLimit(): ?string
    {
        if (! AppSetting::bool('global_speed_enabled', false)) {
            return null;
        }

        $upload   = (int) AppSetting::get('global_speed_upload', 0);
        $download = (int) AppSetting::get('global_speed_download', 0);

        if (! $upload && ! $download) {
            return null;
        }

        return "{$upload}k/{$download}k";
    }
}
