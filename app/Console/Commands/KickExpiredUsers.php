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

        // Users whose plan has expired and who still have active sessions
        $expiredUsers = User::whereNotNull('plan_expiry')
            ->where('plan_expiry', '<=', now())
            ->whereHas('radaccts', function ($q) {
                $q->whereNull('acctstoptime');
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
                try {
                    $this->sendRadiusDisconnect($user->username, (int) config('services.radius.disconnect_timeout', 3));
                } catch (\Throwable $e) {
                    Log::warning('Router unreachable during expiry disconnect', [
                        'user' => $user->username,
                        'session_id' => $session->acctsessionid ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Force-close session in DB regardless of router response
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

            $disconnectedUsers++;
        }

        $this->info("Checked {$checkedUsers} users. Disconnected {$disconnectedUsers} users.");
    }

    /**
     * Attempt a RADIUS CoA/Disconnect with a short timeout.
     */
    protected function sendRadiusDisconnect(string $username, int $timeoutSeconds = 3): bool
    {
        try {
            $radius = new \Net_RADIUS(config('services.radius.server'), config('services.radius.secret'), 1812);
            $radius->addAttribute('User-Name', $username);
            $radius->addAttribute('Acct-Session-Id', 'expire_disconnect');

            if (method_exists($radius, 'setOption')) {
                $radius->setOption('timeout', max(1, $timeoutSeconds));
            }

            $result = $radius->sendRequest(\Net_RADIUS::DISCONNECT_REQUEST);
            return $result === \Net_RADIUS::DISCONNECT_ACK;
        } catch (\Throwable $e) {
            Log::error('RADIUS disconnect failed (expire)', [
                'user' => $username,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}