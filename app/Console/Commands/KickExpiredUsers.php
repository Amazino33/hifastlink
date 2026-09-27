<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RadAcct;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Services\RouterSessionService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

class KickExpiredUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:kick-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kick users whose plan has expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkedUsers = 0;
        $disconnectedUsers = 0;

        // Fetch open sessions from RadAcct model (which uses the correct RADIUS connection)
        $activeUsernames = RadAcct::whereNull('acctstoptime')
            ->distinct()
            ->pluck('username')
            ->filter()
            ->map(fn ($u) => strtolower($u))
            ->unique()
            ->values()
            ->toArray();

        // Expired users who either have an active session or are currently marked active
        $expiredUsers = User::whereNotNull('plan_expiry')
            ->where('plan_expiry', '<=', now())
            ->where(function ($q) use ($activeUsernames) {
                if (!empty($activeUsernames)) {
                    $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(username)'), $activeUsernames)
                      ->orWhere('connection_status', 'active');
                } else {
                    $q->where('connection_status', 'active');
                }
            })
            ->get();

        $subscriptionService = app(SubscriptionService::class);
        $routerSessionService = app(RouterSessionService::class);

        foreach ($expiredUsers as $user) {
            $checkedUsers++;

            // 1. Drop active session on MikroTik router via PoD (UDP 3799) and RouterOS REST API
            try {
                $routerSessionService->forceDisconnectUser($user->username);
            } catch (\Throwable $e) {
                Log::warning("KickExpiredUsers: forceDisconnectUser failed for {$user->username}: " . $e->getMessage());
            }

            // 2. Expire the user's plan via SubscriptionService (snapshots rollover, checks queue, updates status)
            try {
                $subscriptionService->expireForExpiry($user);
            } catch (\Throwable $e) {
                Log::error("KickExpiredUsers: expireForExpiry failed for {$user->username}: " . $e->getMessage());
            }

            // 3. Immediately block reconnection in RADIUS
            try {
                RadReply::updateOrCreate(
                    ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                    ['op' => ':=', 'value' => '0']
                );

                RadCheck::updateOrCreate(
                    ['username' => $user->username, 'attribute' => 'Expiration'],
                    ['op' => ':=', 'value' => now()->subMinute()->format('d M Y H:i')]
                );
            } catch (\Throwable $e) {
                Log::warning("KickExpiredUsers: RadReply/RadCheck block failed for {$user->username}: " . $e->getMessage());
            }

            $disconnectedUsers++;
        }

        $this->info("Checked {$checkedUsers} users. Disconnected {$disconnectedUsers} users.");
    }
}