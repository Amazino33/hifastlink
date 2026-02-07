<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;

class MigrateDefaultRouter extends Command
{
    protected $signature = 'router:migrate-default';
    protected $description = 'Migrate existing router configuration to database';

    public function handle()
    {
        $this->info('Migrating default router configuration...');

        // Get current MikroTik configuration from .env
        $apiHost = env('MIKROTIK_API_HOST', '192.168.88.1');
        $apiUser = env('MIKROTIK_API_USER', 'admin');
        $apiPassword = env('MIKROTIK_API_PASSWORD');
        $radiusSecret = env('RADIUS_SECRET_KEY', 'testing123');

        $router = Router::updateOrCreate(
            ['ip_address' => $apiHost],
            [
                'name' => 'Main Hub',
                'location' => 'Primary Location',
                'nas_identifier' => 'router_main_01',
                'secret' => $radiusSecret,
                'api_user' => $apiUser,
                'api_password' => $apiPassword,
                'api_port' => 8728,
                'is_active' => true,
                'description' => 'Primary router (auto-migrated from .env configuration)',
            ]
        );

        $this->info("✓ Router created: {$router->name} ({$router->ip_address})");
        $this->info("✓ NAS entry synced to RADIUS database");
        $this->newLine();
        $this->line('You can now manage routers via Admin Panel → Routers');
        
        return Command::SUCCESS;
    }
}
