<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RadAcct;
use App\Models\RadReply;
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
        // Get usernames with active sessions
        $activeUsernames = RadAcct::whereNull('acctstoptime')->pluck('username')->toArray();

        // Find users with expired plans and active sessions
        $expiredUsers = User::where('plan_expiry', '<', now())
            ->whereIn('username', $activeUsernames)
            ->get();

        foreach ($expiredUsers as $user) {
            // Update RadReply to set data limit to 0, which will disconnect the user
            RadReply::updateOrCreate(
                [
                    'username' => $user->username,
                    'attribute' => 'Mikrotik-Total-Limit',
                ],
                [
                    'op' => ':=',
                    'value' => '0',
                ]
            );

            // Update user status
            $user->connection_status = 'inactive';
            $user->save();

            // Log the action
            Log::info("Kicked expired user by setting data limit to 0: {$user->username}");
        }

        $this->info('Expired users kicked: ' . $expiredUsers->count());
    }
}