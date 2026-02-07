<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;

class AddRouter extends Command
{
    protected $signature = 'router:add
                            {--name= : Router name (e.g., "Uyo Hub")}
                            {--location= : Router location/address}
                            {--ip= : Router IP address}
                            {--nas-id= : NAS identifier (e.g., "router_uyo_01")}
                            {--secret= : RADIUS secret (default: from .env)}
                            {--api-user= : MikroTik API username}
                            {--api-pass= : MikroTik API password}
                            {--api-port=8728 : MikroTik API port}
                            {--description= : Optional description}';

    protected $description = 'Add a new router to the system and sync with RADIUS';

    public function handle()
    {
        $this->info('🚀 HiFastLink Router Registration Wizard');
        $this->newLine();

        // Gather information (interactive or from options)
        $name = $this->option('name') ?: $this->ask('Router Name (e.g., "Uyo Hub")');
        $location = $this->option('location') ?: $this->ask('Location/Address');
        $ip = $this->option('ip') ?: $this->ask('Router IP Address');
        $nasId = $this->option('nas-id') ?: $this->ask('NAS Identifier', 'router_' . strtolower(str_replace(' ', '_', $name)));
        $secret = $this->option('secret') ?: $this->ask('RADIUS Secret', env('RADIUS_SECRET_KEY', 'testing123'));
        $apiUser = $this->option('api-user') ?: $this->ask('MikroTik API Username (optional)', 'admin');
        $apiPass = $this->option('api-pass') ?: $this->secret('MikroTik API Password (optional)');
        $apiPort = $this->option('api-port') ?: 8728;
        $description = $this->option('description') ?: $this->ask('Description (optional)', '');

        // Validate IP address
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error('Invalid IP address format!');
            return Command::FAILURE;
        }

        // Check for duplicates
        if (Router::where('ip_address', $ip)->exists()) {
            $this->error("Router with IP {$ip} already exists!");
            return Command::FAILURE;
        }

        if (Router::where('nas_identifier', $nasId)->exists()) {
            $this->error("Router with NAS identifier {$nasId} already exists!");
            return Command::FAILURE;
        }

        // Display summary
        $this->newLine();
        $this->line('📋 Summary:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $name],
                ['Location', $location],
                ['IP Address', $ip],
                ['NAS Identifier', $nasId],
                ['RADIUS Secret', str_repeat('*', strlen($secret))],
                ['API User', $apiUser ?: 'Not set'],
                ['API Password', $apiPass ? str_repeat('*', strlen($apiPass)) : 'Not set'],
                ['API Port', $apiPort],
                ['Description', $description ?: 'None'],
            ]
        );

        if (!$this->confirm('Create this router?', true)) {
            $this->warn('Operation cancelled.');
            return Command::FAILURE;
        }

        // Create router
        try {
            $router = Router::create([
                'name' => $name,
                'location' => $location,
                'ip_address' => $ip,
                'nas_identifier' => $nasId,
                'secret' => $secret,
                'api_user' => $apiUser ?: null,
                'api_password' => $apiPass ?: null,
                'api_port' => $apiPort,
                'is_active' => true,
                'description' => $description,
            ]);

            $this->newLine();
            $this->info('✅ Router created successfully!');
            $this->line("   ID: {$router->id}");
            $this->line("   Name: {$router->name}");
            $this->line("   IP: {$router->ip_address}");
            $this->newLine();
            $this->info('✅ Router automatically synced to RADIUS NAS table');
            $this->newLine();

            // Generate MikroTik configuration
            if ($this->confirm('Generate MikroTik configuration script?', true)) {
                $this->generateMikroTikConfig($router);
            }

            $this->newLine();
            $this->line('🎉 Router setup complete!');
            $this->line('📍 View in Admin Panel: /admin/routers');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create router: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function generateMikroTikConfig(Router $router)
    {
        $radiusServer = env('RADIUS_DB_HOST', '142.93.47.189');
        
        $config = <<<EOT
# MikroTik Configuration for {$router->name}
# Generated: {date('Y-m-d H:i:s')}

:log info "Configuring router: {$router->name}"

# RADIUS Configuration
/radius remove [find]
/radius add address={$radiusServer} secret={$router->secret} service=hotspot timeout=3s

# Hotspot Profile
/ip hotspot profile set [find name=hsprof1] \\
    use-radius=yes \\
    radius-accounting=yes \\
    radius-interim-update=1m \\
    shared-users=10

# API Access
/ip service set api disabled=no port={$router->api_port}
:if ([/user find name={$router->api_user}] = "") do={
    /user add name={$router->api_user} password={$router->api_password} group=full
}

# Set Identity
/system identity set name="{$router->name}"

:log info "Configuration complete for {$router->name}"
EOT;

        $filename = storage_path('app/router-configs/') . 'router-' . $router->nas_identifier . '.rsc';
        
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        file_put_contents($filename, $config);

        $this->newLine();
        $this->info('✅ MikroTik configuration file generated!');
        $this->line("   Location: {$filename}");
        $this->newLine();
        $this->line('📝 To apply on router:');
        $this->line("   1. Connect: ssh admin@{$router->ip_address}");
        $this->line("   2. Upload config file via FTP");
        $this->line("   3. Run: /import " . basename($filename));
    }
}
