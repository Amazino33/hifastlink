<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Models\Nas;
use App\Models\RadAcct;

class CheckRouter extends Command
{
    protected $signature = 'router:check {id : Router ID or IP address}';
    
    protected $description = 'Check router configuration and status';

    public function handle()
    {
        $identifier = $this->argument('id');
        
        // Find router by ID or IP
        $router = is_numeric($identifier) 
            ? Router::find($identifier)
            : Router::where('ip_address', $identifier)->first();

        if (!$router) {
            $this->error("Router not found: {$identifier}");
            return Command::FAILURE;
        }

        $this->info("🔍 Checking router: {$router->name}");
        $this->newLine();

        // Basic Info
        $this->line('📋 Basic Information:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $router->id],
                ['Name', $router->name],
                ['Location', $router->location],
                ['IP Address', $router->ip_address],
                ['NAS Identifier', $router->nas_identifier],
                ['Status', $router->is_active ? '🟢 Active' : '🔴 Inactive'],
                ['Created', $router->created_at->format('Y-m-d H:i:s')],
            ]
        );

        // RADIUS Sync Check
        $this->newLine();
        $this->line('🔐 RADIUS Integration:');
        
        $nas = Nas::where('nasname', $router->ip_address)->first();
        
        if ($nas) {
            $this->info("  ✅ Synced to RADIUS NAS table");
            $this->table(
                ['Field', 'Value'],
                [
                    ['NAS Name', $nas->nasname],
                    ['Short Name', $nas->shortname],
                    ['Type', $nas->type],
                    ['Ports', $nas->ports],
                    ['Secret', str_repeat('*', strlen($router->secret))],
                    ['Description', $nas->description],
                ]
            );
        } else {
            $this->error("  ❌ NOT synced to RADIUS NAS table");
            $this->warn("  Run: php artisan tinker → \$router->save() to sync");
        }

        // Active Sessions
        $this->newLine();
        $this->line('👥 Active Sessions:');
        
        $activeSessions = RadAcct::where('nasipaddress', $router->ip_address)
            ->whereNull('acctstoptime')
            ->get();

        if ($activeSessions->count() > 0) {
            $this->info("  {$activeSessions->count()} active session(s)");
            
            $sessions = $activeSessions->map(function($session) {
                return [
                    $session->username,
                    $session->framedipaddress,
                    $session->acctstarttime->diffForHumans(),
                    number_format(($session->acctinputoctets + $session->acctoutputoctets) / 1048576, 2) . ' MB',
                ];
            });

            $this->table(
                ['Username', 'IP', 'Connected', 'Data Used'],
                $sessions
            );
        } else {
            $this->warn("  No active sessions");
        }

        // Statistics
        $this->newLine();
        $this->line('📊 Statistics:');
        
        $todaySessions = RadAcct::where('nasipaddress', $router->ip_address)
            ->whereDate('acctstarttime', today())
            ->count();
            
        $todayBandwidth = RadAcct::where('nasipaddress', $router->ip_address)
            ->whereDate('acctstarttime', today())
            ->sum(\DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
            
        $uniqueUsersToday = RadAcct::where('nasipaddress', $router->ip_address)
            ->whereDate('acctstarttime', today())
            ->distinct('username')
            ->count('username');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Sessions Today', $todaySessions],
                ['Unique Users Today', $uniqueUsersToday],
                ['Bandwidth Today', number_format($todayBandwidth / 1073741824, 2) . ' GB'],
                ['Currently Active', $activeSessions->count()],
            ]
        );

        // API Configuration
        $this->newLine();
        $this->line('🔌 API Configuration:');
        
        if ($router->api_user) {
            $this->info("  ✅ API configured");
            $this->table(
                ['Field', 'Value'],
                [
                    ['API User', $router->api_user],
                    ['API Password', str_repeat('*', strlen($router->api_password ?? ''))],
                    ['API Port', $router->api_port],
                ]
            );
        } else {
            $this->warn("  ⚠️  API not configured");
            $this->line("  Configure via: php artisan router:add or admin panel");
        }

        // Health Checks
        $this->newLine();
        $this->line('🏥 Health Checks:');
        
        $checks = [];
        
        // Check 1: NAS sync
        $checks[] = [
            'NAS Sync',
            $nas ? '✅ Pass' : '❌ Fail',
            $nas ? 'Synced to RADIUS' : 'Not synced - run $router->save()',
        ];
        
        // Check 2: Active status
        $checks[] = [
            'Active Status',
            $router->is_active ? '✅ Pass' : '⚠️  Warning',
            $router->is_active ? 'Router is active' : 'Router is inactive',
        ];
        
        // Check 3: Recent activity
        $recentActivity = RadAcct::where('nasipaddress', $router->ip_address)
            ->where('acctstarttime', '>=', now()->subHours(24))
            ->exists();
            
        $checks[] = [
            'Recent Activity',
            $recentActivity ? '✅ Pass' : '⚠️  Warning',
            $recentActivity ? 'Activity in last 24h' : 'No activity in 24h',
        ];
        
        // Check 4: Configuration complete
        $configComplete = $router->api_user && $router->secret;
        $checks[] = [
            'Configuration',
            $configComplete ? '✅ Pass' : '⚠️  Warning',
            $configComplete ? 'Configuration complete' : 'Missing API or secret',
        ];

        $this->table(['Check', 'Status', 'Details'], $checks);

        // Recommendations
        $this->newLine();
        $this->line('💡 Recommendations:');
        
        $recommendations = [];
        
        if (!$nas) {
            $recommendations[] = "• Sync to RADIUS: Run \$router->save() in tinker";
        }
        
        if (!$router->is_active) {
            $recommendations[] = "• Activate router via admin panel or: \$router->is_active = true; \$router->save()";
        }
        
        if (!$recentActivity) {
            $recommendations[] = "• No recent activity - check MikroTik configuration";
            $recommendations[] = "• Verify RADIUS settings: /radius print";
            $recommendations[] = "• Check hotspot profile: /ip hotspot profile print detail";
        }
        
        if (!$router->api_user) {
            $recommendations[] = "• Configure API credentials for remote management";
        }
        
        if ($activeSessions->count() > 50) {
            $recommendations[] = "• High load: {$activeSessions->count()} active sessions - consider adding capacity";
        }
        
        if (empty($recommendations)) {
            $this->info("  ✅ All checks passed! Router is healthy.");
        } else {
            foreach ($recommendations as $rec) {
                $this->warn($rec);
            }
        }

        $this->newLine();
        $this->info('✅ Router check complete!');

        return Command::SUCCESS;
    }
}
