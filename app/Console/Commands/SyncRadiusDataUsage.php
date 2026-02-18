<?php

namespace App\Console\Commands;

use App\Models\RadAcct;
use App\Models\User;
use App\Services\RadiusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncRadiusDataUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radius:sync-data-usage {--user=* : Specific user IDs to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user data usage from RADIUS accounting database';

    protected RadiusService $radiusService;

    /**
     * Create a new command instance.
     */
    public function __construct(RadiusService $radiusService)
    {
        parent::__construct();
        $this->radiusService = $radiusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIds = $this->option('user');

        if (!empty($userIds)) {
            // Sync specific users
            $users = User::whereIn('id', $userIds)->get();
            $this->info("Syncing data usage for " . $users->count() . " specific user(s)...");
        } else {
            // Sync all active users
            $users = User::whereNotNull('username')
                ->where(function ($query) {
                    $query->whereNull('subscription_end_date')
                        ->orWhere('subscription_end_date', '>', now());
                })
                ->get();
            $this->info("Syncing data usage for all active users (" . $users->count() . " users)...");
        }

        // ── Step 1: 3-Strike stale-session detection ─────────────────────────
        // Runs BEFORE the main sync so that ghost sessions are closed and
        // updateConnectionStatus() reflects the corrected state below.
        $this->detectAndCloseStaleSessions();
        // ─────────────────────────────────────────────────────────────────────

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $successCount = 0;
        $failCount    = 0;

        foreach ($users as $user) {
            try {
                // Sync data usage
                $this->radiusService->syncUserDataUsage($user);

                // Update connection status (will now reflect stale-closed sessions)
                $this->radiusService->updateConnectionStatus($user);

                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nFailed to sync user {$user->username}: " . $e->getMessage());
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Sync completed!");
        $this->info("✓ Success: {$successCount}");

        if ($failCount > 0) {
            $this->warn("✗ Failed: {$failCount}");
        }

        return Command::SUCCESS;
    }

    /**
     * 3-Strike Rule: detect sessions where acctsessiontime has stopped advancing,
     * which indicates the router disconnected abruptly without sending Acct-Stop.
     *
     * Cache keys (TTL 5 min):
     *   radius.session.{acctuniqueid}  →  ['last_time' => int, 'strikes' => int]
     */
    private function detectAndCloseStaleSessions(): void
    {
        // Fetch ALL open sessions (no time-window filter — we want to catch
        // sessions the active() scope would already hide from the dashboard).
        $openSessions = RadAcct::whereNull('acctstoptime')->get();

        $staleCount = 0;

        foreach ($openSessions as $session) {
            $cacheKey = "radius.session.{$session->acctuniqueid}";

            /** @var array{last_time: int|null, strikes: int} $cached */
            $cached      = Cache::get($cacheKey, ['last_time' => null, 'strikes' => 0]);
            $currentTime = (int) $session->acctsessiontime;
            $lastTime    = $cached['last_time'];
            $strikes     = (int) $cached['strikes'];

            if ($lastTime === null || $currentTime > $lastTime) {
                // ✅ Session is still alive — reset strikes and store new baseline.
                Cache::put(
                    $cacheKey,
                    ['last_time' => $currentTime, 'strikes' => 0],
                    now()->addMinutes(5)
                );
            } else {
                // ⚠  acctsessiontime has NOT advanced since last check — add a strike.
                $strikes++;

                if ($strikes >= 3) {
                    // ❌ 3 strikes reached — router is considered gone.
                    // Close the session in the DB.
                    $session->update([
                        'acctstoptime'       => now(),
                        'acctterminatecause' => 'Stale-Check',
                    ]);

                    // Mark the user offline immediately.
                    User::whereRaw('LOWER(username) = ?', [strtolower($session->username)])
                        ->update([
                            'connection_status' => 'offline',
                            'online_status'     => false,
                            'last_online'       => now(),
                            'current_ip'        => null,
                        ]);

                    Log::info('RadiusSync: Stale session forcefully closed', [
                        'acctuniqueid'     => $session->acctuniqueid,
                        'username'         => $session->username,
                        'nasipaddress'     => $session->nasipaddress,
                        'last_sessiontime' => $lastTime,
                        'strikes'          => $strikes,
                    ]);

                    // Remove the cache entry — session is dead.
                    Cache::forget($cacheKey);

                    $staleCount++;
                } else {
                    // Keep counting — update the cache with the incremented strike tally.
                    Cache::put(
                        $cacheKey,
                        ['last_time' => $currentTime, 'strikes' => $strikes],
                        now()->addMinutes(5)
                    );
                }
            }
        }

        if ($staleCount > 0) {
            $this->warn("⚠  Stale sessions closed: {$staleCount}");
        } else {
            $this->line('  Stale-session check: no dead sessions detected.');
        }
    }
}
