<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RadAcct;
use App\Models\RadReply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        // Users whose plan has expired and who still have active sessions (collation-safe)
        $expiredUsers = User::whereNotNull('plan_expiry')
            ->where('plan_expiry', '<=', now())
            ->whereExists(function ($q) use ($collation) {
                $q->select(DB::raw(1))
                    ->from('radacct')
                    ->whereNull('radacct.acctstoptime')
                    ->whereRaw("radacct.username COLLATE {$collation} = users.username COLLATE {$collation}");
            })
            ->get();

        foreach ($expiredUsers as $user) {
            $checkedUsers++;

            $sessions = RadAcct::where('username', $user->username)
                ->whereNull('acctstoptime')
                ->get();

            if ($sessions->isEmpty()) {
                continue;
            }

            $updateData = [
                'acctstoptime' => now(),
                'acctterminatecause' => 'Session-Timeout',
            ];

            foreach ($sessions as $session) {
                // Force-close session in DB — this is the authoritative disconnect.
                // A RADIUS CoA/PoD packet would also terminate the live TCP session,
                // but the web server cannot reach the MikroTik LAN IP from shared hosting.
                // Mikrotik-Total-Limit=0 (set below) blocks data on any mac-cookie reconnect.
                $query = DB::table('radacct')
                    ->where('username', $user->username)
                    ->whereNull('acctstoptime');

                if (!empty($session->radacctid)) {
                    $query->where('radacctid', $session->radacctid);
                }

                if (!empty($session->callingstationid)) {
                    $query->where('callingstationid', $session->callingstationid);
                }

                $query->update($updateData);
            }

            // Block reconnection immediately — mac-cookie would otherwise re-auth and get
            // a new session before subscriptions:check-expiry runs expireForExpiry().
            RadReply::updateOrCreate(
                ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                ['op' => ':=', 'value' => '0']
            );

            $disconnectedUsers++;
        }

        $this->info("Checked {$checkedUsers} users. Disconnected {$disconnectedUsers} users.");
    }
}