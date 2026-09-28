<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SyncRadius extends Command
{
    protected $signature = 'sync:radius {--dry-run}';
    protected $description = 'Sync users online status and data usage from radacct';

    public function handle()
    {
        $dry = $this->option('dry-run');

        $this->info('Starting RADIUS sync' . ($dry ? ' (dry run)' : '') . '...');

        try {
            // 1) Mark online users (active sessions: acctstoptime IS NULL)
            $connName = (new \App\Models\RadAcct)->getConnectionName();
            if (!\Illuminate\Support\Facades\Schema::connection($connName)->hasTable('radacct')) {
                $this->error('radacct table not found. Aborting.');
                return 1;
            }

            $activeUsernames = \App\Models\RadAcct::whereNull('acctstoptime')
                ->distinct()
                ->pluck('username')
                ->toArray();

            foreach ($activeUsernames as $username) {
                $user = User::where('username', $username)->first();
                if (! $user) {
                    Log::info("sync: user not found for username {$username}");
                    $this->line("skip: user not found {$username}");
                    continue;
                }

                $session = \App\Models\RadAcct::where('username', $username)
                    ->whereNull('acctstoptime')
                    ->orderByDesc('acctstarttime')
                    ->first();

                $sessionBytes = (int) ($session->acctinputoctets ?? 0) + (int) ($session->acctoutputoctets ?? 0);
                $ip = $session->nasipaddress ?? $session->framedipaddress ?? null;

                $this->info("Active: {$username} bytes={$sessionBytes} ip={$ip}");

                if (! $dry) {
                    $user->online_status = true;
                    $user->current_ip = $ip ?? $user->current_ip;
                    $user->last_online = now();
                    // don't overwrite data_used here, we'll recalc total from radacct below
                    $user->save();

                    Log::info("sync: marked {$username} online, session_bytes={$sessionBytes}");
                }
            }

            // 2) Mark offline users (no active sessions)
            $allUsernamesWithAcct = \App\Models\RadAcct::distinct()->pluck('username')->toArray();
            $usersToCheck = User::whereIn('username', $allUsernamesWithAcct)->get();
            foreach ($usersToCheck as $user) {
                if (in_array($user->username, $activeUsernames)) {
                    continue; // already handled
                }

                $this->info("Offline: {$user->username}");

                if (! $dry) {
                    $user->online_status = false;
                    $user->save();
                }
            }

            // 3) Recompute total usage per user from radacct (idempotent)
            $usernames = \App\Models\RadAcct::distinct()->pluck('username');

            foreach ($usernames as $username) {
                $user = User::where('username', $username)->first();
                if (! $user) {
                    Log::info("sync: user not found while summing usage for {$username}");
                    continue;
                }

                // Don't touch data_used for users with no active plan — freeze it at
                // the value written when the plan was last cleared, so the counter doesn't
                // keep climbing from stale historical sessions after expiry.
                if (! $user->plan_id && ! $user->plan_expiry) {
                    continue;
                }

                // Sum only bytes from the current plan period (since plan_started_at).
                // This keeps data_used consistent with what PlanSyncService writes to
                // Mikrotik-Total-Limit, which also filters by plan_started_at.
                $masterId = $user->parent_id ?? $user->id;
                $familyUsernames = ($user->is_family_admin || $user->parent_id)
                    ? User::where('id', $masterId)->orWhere('parent_id', $masterId)->pluck('username')->filter()->toArray()
                    : [$username];

                $totalQuery = \App\Models\RadAcct::whereIn('username', $familyUsernames);
                if ($user->plan_started_at) {
                    $startUtc = \Illuminate\Support\Carbon::parse($user->plan_started_at)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $totalQuery->where(function ($q) use ($startUtc) {
                        $q->whereNull('acctstoptime')
                          ->orWhere('acctstoptime', '>=', $startUtc)
                          ->orWhere('acctstarttime', '>=', $startUtc);
                    });
                }

                $hasGigawords = false;
                try {
                    $hasGigawords = \Illuminate\Support\Facades\Schema::connection((new \App\Models\RadAcct)->getConnectionName())->hasColumn('radacct', 'acctinputgigawords');
                } catch (\Throwable $e) {}

                $expr = $hasGigawords
                    ? 'COALESCE(SUM(COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0) + (COALESCE(acctinputgigawords,0) * 4294967296) + (COALESCE(acctoutputgigawords,0) * 4294967296)),0) AS total'
                    : 'COALESCE(SUM(COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)),0) AS total';

                $total = $totalQuery->selectRaw($expr)->value('total');

                $this->info("Usage: {$username} total_bytes={$total}");

                if (! $dry) {
                    $user->data_used = (int) $total;
                    $user->save();
                    Log::info("sync: updated {$username} data_used={$total}");

                    // Check if user has exhausted their data
                    if ($user->hasExceededDataLimit() && ($user->plan_id || $user->data_limit > 0)) {
                        $this->warn("Data exhausted for {$username} - clearing ALL plan details and disconnecting");
                        
                        // Clear ALL plan-related fields
                        $user->plan_id = null;
                        $user->data_plan_id = null;
                        $user->plan_expiry = null;
                        $user->plan_started_at = null;
                        $user->data_limit = 0; // Set to 0 instead of null (NOT NULL constraint)
                        $user->data_used = (int) $total; // Keep the usage record for history
                        $user->subscription_start_date = null;
                        $user->subscription_end_date = null;
                        $user->connection_status = 'exhausted';
                        $user->save();

                        // Disconnect active sessions in radacct
                        \App\Models\RadAcct::where('username', $username)
                            ->whereNull('acctstoptime')
                            ->update([
                                'acctstoptime' => now(),
                                'acctterminatecause' => 'Data-Limit-Exceeded',
                            ]);

                        // Remove RADIUS credentials
                        \App\Models\RadCheck::where('username', $username)->delete();
                        \App\Models\RadReply::where('username', $username)->delete();

                        // Immediately block reconnection
                        \App\Models\RadReply::updateOrCreate(
                            ['username' => $username, 'attribute' => 'Mikrotik-Total-Limit'],
                            ['op' => ':=', 'value' => '0']
                        );
                        \App\Models\RadCheck::updateOrCreate(
                            ['username' => $username, 'attribute' => 'Expiration'],
                            ['op' => ':=', 'value' => now()->subMinute()->format('d M Y H:i')]
                        );

                        // Forcibly disconnect active session on MikroTik router
                        try {
                            app(\App\Services\RouterSessionService::class)->forceDisconnectUser($username);
                        } catch (\Throwable $te) {
                            Log::warning("sync: failed router disconnect for {$username}: " . $te->getMessage());
                        }

                        Log::info("sync: data exhausted for {$username}, plan cleared and user disconnected");
                    }
                }
            }

            $this->info('RADIUS sync completed.');
            return 0;
        } catch (\Exception $e) {
            Log::error('sync:radius failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->error('RADIUS sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}
