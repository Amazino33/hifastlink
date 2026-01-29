<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncRadiusUsers extends Command
{
    protected $signature = 'radius:sync-users';
    protected $description = 'Sync users from Laravel to FreeRADIUS database via bridge script';

    public function handle()
    {
        $this->info('Syncing users to FreeRADIUS via bridge script...');

        try {
            // First, let's see what users we have
            $allUsers = User::all();
            $this->info("Total users in database: {$allUsers->count()}");

            $users = User::where('connection_status', '!=', 'suspended')
                        ->whereNotNull('username')
                        ->get();

            $this->info("Users eligible for sync (not suspended, have username): {$users->count()}");

            // Show details of eligible users
            if ($users->count() > 0) {
                $this->info('Eligible users:');
                foreach ($users as $user) {
                    $this->line("  - {$user->username} ({$user->email}) - Status: {$user->connection_status}");
                }
            } else {
                $this->warn('No users eligible for sync. Checking why...');

                $noUsername = User::whereNull('username')->count();
                $suspended = User::where('connection_status', 'suspended')->count();
                $inactive = User::where('connection_status', 'inactive')->count();

                $this->line("Users with no username: {$noUsername}");
                $this->line("Suspended users: {$suspended}");
                $this->line("Inactive users: {$inactive}");

                $this->warn('To fix this, run: php artisan db:seed');
                $this->warn('Or manually set usernames and status for existing users');
                return 0;
            }

            $successCount = 0;
            $errorCount = 0;

            foreach ($users as $user) {
                $result = $this->syncUserToRadius($user);

                if ($result['success']) {
                    $successCount++;
                    $this->info("✓ Synced user: {$user->username}");
                } else {
                    $errorCount++;
                    $this->error("✗ Failed to sync user: {$user->username} - {$result['message']}");
                }
            }

            $this->info("Sync completed! Success: {$successCount}, Errors: {$errorCount}");

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function syncUserToRadius(User $user): array
    {
        try {
            // Bridge script URL - update this with your Ubuntu server's URL
            $bridgeUrl = config('services.radius.bridge_url', 'http://your-ubuntu-server-ip/radius_bridge.php');
            $secretKey = config('services.radius.secret_key', 'MySecretKey_ChangeThisToSomethingComplex');

            // DEBUG: Log the values being sent
            \Log::info('RADIUS Sync - Attempting to sync user', [
                'username' => $user->username,
                'bridge_url' => $bridgeUrl,
            ]);

            $payload = [
                'key' => $secretKey,
                'username' => $user->username,
                'password' => $user->radius_password ?: $user->username, // Use radius_password or fallback to username
            ];

            // Add time limit if subscription has end date
            if ($user->subscription_end_date) {
                $remainingSeconds = now()->diffInSeconds($user->subscription_end_date, false);
                if ($remainingSeconds > 0) {
                    $payload['time_limit'] = $remainingSeconds;
                }
            }

            $response = Http::timeout(10)->asForm()->post($bridgeUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? null) === 'success') {
                    return ['success' => true, 'message' => 'User synced successfully'];
                } else {
                    return ['success' => false, 'message' => $data['message'] ?? 'Unknown error'];
                }
            } else {
                return ['success' => false, 'message' => 'HTTP ' . $response->status() . ': ' . $response->body()];
            }

        } catch (\Exception $e) {
            \Log::error('RADIUS Sync failed', [
                'error' => $e->getMessage(),
                'user' => $user->username ?? 'unknown',
            ]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
}