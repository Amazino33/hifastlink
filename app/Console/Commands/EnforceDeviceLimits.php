<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RadAcct;
use App\Models\RadCheck;
use App\Models\User;
use App\Services\MikroTikApiService;

class EnforceDeviceLimits extends Command
{
    protected $signature = 'radius:enforce-limits {--disconnect : Disconnect excess sessions} {--clean-stale : Clean stale sessions}';
    
    protected $description = 'Enforce Simultaneous-Use device limits';

    public function handle()
    {
        $this->info('🔍 Checking device limits...');
        $this->newLine();

        // Clean stale sessions first if requested
        if ($this->option('clean-stale')) {
            $this->cleanStaleSessions();
        }

        $users = User::whereNotNull('username')->get();
        $violations = [];
        $total = 0;

        foreach ($users as $user) {
            $limit = RadCheck::where('username', $user->username)
                ->where('attribute', 'Simultaneous-Use')
                ->value('value');
            
            if (!$limit) {
                continue;
            }
            
            $total++;
            
            $activeSessions = RadAcct::where('username', $user->username)
                ->whereNull('acctstoptime')
                ->orderBy('acctstarttime', 'asc')
                ->get();
            
            $active = $activeSessions->count();
            
            if ($active > $limit) {
                $violations[] = [
                    'user' => $user,
                    'limit' => $limit,
                    'active' => $active,
                    'sessions' => $activeSessions,
                    'excess' => $active - $limit
                ];
            }
        }

        if (empty($violations)) {
            $this->info("✅ All $total users are within their device limits.");
            return Command::SUCCESS;
        }

        // Display violations
        $this->error("❌ Found " . count($violations) . " user(s) exceeding device limits:");
        $this->newLine();

        $tableData = [];
        foreach ($violations as $v) {
            $tableData[] = [
                $v['user']->username,
                $v['limit'],
                $v['active'],
                $v['excess'],
            ];
        }

        $this->table(
            ['Username', 'Limit', 'Active', 'Excess'],
            $tableData
        );

        // Show detailed sessions for each violation
        foreach ($violations as $v) {
            $this->newLine();
            $this->warn("📱 Sessions for {$v['user']->username}:");
            
            $sessionData = [];
            foreach ($v['sessions'] as $session) {
                $sessionData[] = [
                    $session->framedipaddress,
                    $session->nasipaddress,
                    $session->callingstationid,
                    $session->acctstarttime->format('Y-m-d H:i:s'),
                    $session->acctstarttime->diffForHumans(),
                ];
            }
            
            $this->table(
                ['IP', 'NAS IP', 'MAC', 'Started', 'Duration'],
                $sessionData
            );
        }

        // Disconnect excess sessions if requested
        if ($this->option('disconnect')) {
            if (!$this->confirm('Disconnect excess sessions from all routers?', false)) {
                $this->info('Skipped disconnection.');
                return Command::SUCCESS;
            }

            $disconnected = 0;
            foreach ($violations as $v) {
                // Keep the most recent sessions up to the limit
                $sessionsToDisconnect = $v['sessions']->take($v['excess']);
                
                foreach ($sessionsToDisconnect as $session) {
                    try {
                        // Find router by NAS IP
                        $router = \App\Models\Router::where('ip_address', $session->nasipaddress)->first();
                        
                        if (!$router || !$router->api_user) {
                            $this->warn("  ⚠️  Cannot disconnect {$session->username} from {$session->nasipaddress} - No router config");
                            continue;
                        }

                        // Disconnect via MikroTik API
                        $mikrotik = new MikroTikApiService(
                            $router->ip_address,
                            $router->api_user,
                            $router->api_password,
                            $router->api_port ?? 8728
                        );

                        $result = $mikrotik->disconnectUser($v['user']->username, $session->framedipaddress);
                        
                        if ($result['success']) {
                            $this->info("  ✅ Disconnected {$session->framedipaddress} from {$router->name}");
                            
                            // Update radacct
                            $session->update([
                                'acctstoptime' => now(),
                                'acctterminatecause' => 'Admin-Reset'
                            ]);
                            
                            $disconnected++;
                        } else {
                            $this->error("  ❌ Failed: {$result['message']}");
                        }
                    } catch (\Exception $e) {
                        $this->error("  ❌ Error: " . $e->getMessage());
                    }
                }
            }

            $this->newLine();
            $this->info("✅ Disconnected $disconnected excess session(s).");
        } else {
            $this->newLine();
            $this->warn('💡 Run with --disconnect to automatically disconnect excess sessions.');
        }

        $this->newLine();
        $this->warn('⚠️  To prevent this in the future, configure FreeRADIUS session checking:');
        $this->line('   See: FREERADIUS_SIMULTANEOUS_USE.md');

        return Command::SUCCESS;
    }

    private function cleanStaleSessions()
    {
        $this->info('🧹 Cleaning stale sessions...');
        
        // Find sessions stuck open for > 4 hours without updates
        $stale = RadAcct::whereNull('acctstoptime')
            ->where('acctupdatetime', '<', now()->subHours(4))
            ->get();

        if ($stale->count() === 0) {
            $this->info('   No stale sessions found.');
            $this->newLine();
            return;
        }

        $this->warn("   Found {$stale->count()} stale session(s)");
        
        foreach ($stale as $session) {
            $session->update([
                'acctstoptime' => $session->acctupdatetime,
                'acctterminatecause' => 'Lost-Carrier'
            ]);
        }

        $this->info("   ✅ Cleaned {$stale->count()} stale session(s)");
        $this->newLine();
    }
}
