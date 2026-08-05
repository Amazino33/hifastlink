<?php

namespace App\Console\Commands;

use App\Models\RadAcct;
use App\Models\RadCheck;
use App\Models\Router;
use App\Models\User;
use App\Services\PlanSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckRouterOwnerSubscriptions extends Command
{
    protected $signature = 'routers:check-owner-subscriptions';

    protected $description = 'Block routers whose owner\'s plan has expired; unblock when the owner renews';

    public function handle(): void
    {
        $routers = Router::with('owner')
            ->where('requires_owner_subscription', true)
            ->whereNotNull('owner_id')
            ->get();

        foreach ($routers as $router) {
            $owner = $router->owner;

            if (! $owner) {
                continue;
            }

            $ownerHasActivePlan = $owner->plan_id
                && $owner->plan_expiry
                && $owner->plan_expiry->isFuture();

            if (! $ownerHasActivePlan && ! $router->access_blocked) {
                $this->blockRouter($router);
            } elseif ($ownerHasActivePlan && $router->access_blocked) {
                $this->unblockRouter($router);
            }
        }
    }

    private function blockRouter(Router $router): void
    {
        $this->info("Blocking router [{$router->name}] — owner's plan has expired.");

        $users = User::where('router_id', $router->id)->get();

        foreach ($users as $user) {
            if (! $user->username) {
                continue;
            }

            // Add Auth-Type := Reject so FreeRADIUS rejects all auth attempts for this user
            RadCheck::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Auth-Type'],
                ['op' => ':=', 'value' => 'Reject']
            );

            // Disconnect any active RADIUS sessions immediately
            $this->disconnectUserSessions($user->username);
        }

        $router->access_blocked = true;
        $router->saveQuietly();

        Log::warning("Router [{$router->name}] blocked — owner [{$router->owner?->name}] plan expired.", [
            'router_id' => $router->id,
            'users_blocked' => $users->count(),
        ]);
    }

    private function unblockRouter(Router $router): void
    {
        $this->info("Unblocking router [{$router->name}] — owner's plan is now active.");

        $users = User::where('router_id', $router->id)->get();

        foreach ($users as $user) {
            if (! $user->username) {
                continue;
            }

            // Remove the blanket reject so normal plan-based auth resumes
            RadCheck::where('username', $user->username)
                ->where('attribute', 'Auth-Type')
                ->delete();

            // Re-sync the user's plan attributes so they can reconnect immediately
            try {
                PlanSyncService::syncUserPlan($user->fresh());
            } catch (\Throwable $e) {
                Log::error("Failed to re-sync user [{$user->username}] after router unblock: " . $e->getMessage());
            }
        }

        $router->access_blocked = false;
        $router->saveQuietly();

        Log::info("Router [{$router->name}] unblocked — owner's plan renewed.", [
            'router_id' => $router->id,
            'users_restored' => $users->count(),
        ]);
    }

    private function disconnectUserSessions(string $username): void
    {
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        $sessions = RadAcct::whereNull('acctstoptime')
            ->whereRaw("username COLLATE {$collation} = ? COLLATE {$collation}", [$username])
            ->get();

        foreach ($sessions as $session) {
            // Send RADIUS CoA disconnect packet — best effort, won't halt on failure
            try {
                $this->sendRadiusDisconnect($username);
            } catch (\Throwable $e) {
                Log::warning('Router block: RADIUS disconnect failed', [
                    'user'  => $username,
                    'error' => $e->getMessage(),
                ]);
            }

            // Force-close the session in the DB regardless of whether the router responded
            DB::table('radacct')
                ->where('radacctid', $session->radacctid)
                ->whereNull('acctstoptime')
                ->update([
                    'acctstoptime'       => now(),
                    'acctterminatecause' => 'Admin-Reset',
                ]);
        }
    }

    protected function sendRadiusDisconnect(string $username, int $timeoutSeconds = 3): bool
    {
        try {
            $radius = new \Net_RADIUS(config('services.radius.server'), config('services.radius.secret'), 1812);
            $radius->addAttribute('User-Name', $username);
            $radius->addAttribute('Acct-Session-Id', 'router_block');

            if (method_exists($radius, 'setOption')) {
                $radius->setOption('timeout', max(1, $timeoutSeconds));
            }

            $result = $radius->sendRequest(\Net_RADIUS::DISCONNECT_REQUEST);
            return $result === \Net_RADIUS::DISCONNECT_ACK;
        } catch (\Throwable $e) {
            Log::error('RADIUS disconnect failed (router block)', [
                'user'  => $username,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
