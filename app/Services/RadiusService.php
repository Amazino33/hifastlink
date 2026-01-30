<?php

namespace App\Services;

use App\Models\User;
use App\Models\DataPlan;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\RadAcct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RadiusService
{
    /**
     * Create or update RADIUS user credentials.
     */
    public function createRadiusUser(User $user): bool
    {
        try {
            DB::connection('radius')->beginTransaction();

            // Delete existing entries
            RadCheck::where('username', $user->username)->delete();
            RadReply::where('username', $user->username)->delete();

            // Add password check (Cleartext-Password)
            RadCheck::create([
                'username' => $user->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $user->radius_password ?? $user->username, // Use radius_password or fallback
            ]);

            // Add data limit check if applicable
            if ($user->data_limit > 0) {
                RadCheck::create([
                    'username' => $user->username,
                    'attribute' => 'Max-Octets',
                    'op' => ':=',
                    'value' => (string)$user->data_limit,
                ]);
            }

            // Add reply attributes if user has a data plan
            if ($user->dataPlan) {
                $plan = $user->dataPlan;

                // Session timeout (duration in seconds)
                if ($plan->duration_seconds > 0) {
                    RadReply::create([
                        'username' => $user->username,
                        'attribute' => 'Session-Timeout',
                        'op' => ':=',
                        'value' => (string)$plan->duration_seconds,
                    ]);
                }

                // Speed limit (MikroTik specific)
                if ($plan->speed_limit) {
                    RadReply::create([
                        'username' => $user->username,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op' => ':=',
                        'value' => $plan->speed_limit, // e.g., "10M/10M"
                    ]);
                }
            }

            DB::connection('radius')->commit();
            return true;
        } catch (\Exception $e) {
            DB::connection('radius')->rollBack();
            \Log::error('Failed to create RADIUS user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user's data usage from RADIUS accounting.
     */
    public function syncUserDataUsage(User $user): bool
    {
        try {
            // Get total data usage from radacct table
            $totalUsage = RadAcct::forUser($user->username)
                ->selectRaw('SUM(acctinputoctets + acctoutputoctets) as total')
                ->value('total') ?? 0;

            // Update user's data_used
            $user->update([
                'data_used' => (int)$totalUsage,
            ]);

            // Check if user exceeded limit
            if ($user->hasExceededDataLimit()) {
                $this->disableUser($user);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to sync user data usage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active session for a user.
     */
    public function getActiveSession(User $user): ?RadAcct
    {
        return RadAcct::forUser($user->username)
            ->active()
            ->orderBy('acctstarttime', 'desc')
            ->first();
    }

    /**
     * Get all sessions for a user.
     */
    public function getUserSessions(User $user, int $limit = 10)
    {
        return RadAcct::forUser($user->username)
            ->orderBy('acctstarttime', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Disable user by removing RADIUS credentials.
     */
    public function disableUser(User $user): bool
    {
        try {
            RadCheck::where('username', $user->username)->delete();
            RadReply::where('username', $user->username)->delete();

            $user->update([
                'connection_status' => 'disabled',
                'online_status' => false,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to disable RADIUS user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable user by restoring RADIUS credentials.
     */
    public function enableUser(User $user): bool
    {
        return $this->createRadiusUser($user);
    }

    /**
     * Subscribe user to a data plan.
     */
    public function subscribeUserToPlan(User $user, DataPlan $plan): bool
    {
        try {
            DB::beginTransaction();

            // Update user with plan details
            $user->update([
                'data_plan_id' => $plan->id,
                'data_limit' => $plan->data_limit,
                'data_used' => 0,
                'subscription_start_date' => Carbon::now(),
                'subscription_end_date' => Carbon::now()->addDays($plan->duration_days),
                'plan_expiry' => Carbon::now()->addDays($plan->duration_days),
                'connection_status' => 'active',
                'online_status' => false,
            ]);

            // Create/update RADIUS credentials
            $this->createRadiusUser($user);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to subscribe user to plan: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and update user connection status based on session.
     */
    public function updateConnectionStatus(User $user): void
    {
        $activeSession = $this->getActiveSession($user);

        if ($activeSession) {
            $user->update([
                'connection_status' => 'online',
                'online_status' => true,
                'last_online' => Carbon::now(),
                'current_ip' => $activeSession->framedipaddress,
            ]);
        } else {
            $lastSession = RadAcct::forUser($user->username)
                ->orderBy('acctstoptime', 'desc')
                ->first();

            if ($lastSession && $lastSession->acctstoptime) {
                $user->update([
                    'connection_status' => 'offline',
                    'online_status' => false,
                    'last_online' => $lastSession->acctstoptime,
                    'current_ip' => null,
                ]);
            }
        }
    }

    /**
     * Get data usage statistics for a user.
     */
    public function getUserDataStats(User $user): array
    {
        $sessions = RadAcct::forUser($user->username)->get();

        $totalSessions = $sessions->count();
        $activeSessions = $sessions->where('acctstoptime', null)->count();
        $totalTime = $sessions->sum('acctsessiontime');
        $totalDownload = $sessions->sum('acctoutputoctets');
        $totalUpload = $sessions->sum('acctinputoctets');
        $totalData = $totalDownload + $totalUpload;

        return [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'total_time_seconds' => $totalTime,
            'total_time_formatted' => $this->formatDuration($totalTime),
            'total_upload_bytes' => $totalUpload,
            'total_download_bytes' => $totalDownload,
            'total_data_bytes' => $totalData,
            'total_data_formatted' => $this->formatBytes($totalData),
            'last_session' => $sessions->sortByDesc('acctstarttime')->first(),
        ];
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Format duration to human readable.
     */
    protected function formatDuration(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '0m';
    }
}
