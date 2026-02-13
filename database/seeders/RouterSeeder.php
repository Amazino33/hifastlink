<?php

namespace Database\Seeders;

use App\Models\Router;
use Illuminate\Database\Seeder;

class RouterSeeder extends Seeder
{
    public function run(): void
    {
        $routers = [
            [
                'name' => 'Main Office Router',
                'location' => '123 Main Street, Downtown',
                'ip_address' => '192.168.1.1',
                'nas_identifier' => 'router_main_01',
                'secret' => 'radius_secret_123',
                'api_user' => 'admin',
                'api_password' => 'mikrotik_password',
                'api_port' => 8728,
                'is_active' => true,
                'description' => 'Primary router for main office location',
            ],
            [
                'name' => 'Branch Office Router',
                'location' => '456 Branch Avenue, Suburb',
                'ip_address' => '192.168.2.1',
                'nas_identifier' => 'router_branch_01',
                'secret' => 'radius_secret_456',
                'api_user' => 'admin',
                'api_password' => 'mikrotik_password',
                'api_port' => 8728,
                'is_active' => true,
                'description' => 'Router for branch office location',
            ],
            [
                'name' => 'Residential Area Router',
                'location' => '789 Residential Blvd, Neighborhood',
                'ip_address' => '192.168.3.1',
                'nas_identifier' => 'router_residential_01',
                'secret' => 'radius_secret_789',
                'api_user' => 'admin',
                'api_password' => 'mikrotik_password',
                'api_port' => 8728,
                'is_active' => true,
                'description' => 'Router serving residential area',
            ],
        ];

        foreach ($routers as $router) {
            Router::firstOrCreate(
                ['nas_identifier' => $router['nas_identifier']],
                $router
            );
        }

        $this->command->info('Ensured ' . count($routers) . ' routers exist');
    }
}