<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use phpseclib3\Net\SSH2;

class CreateRouter extends CreateRecord
{
    protected static string $resource = RouterResource::class;

    protected function afterCreate(): void
    {
        // $this->record contains the newly created Router model from the database
        $router = $this->record;

        // Ensure we have the required data before running server commands
        if (empty($router->wireguard_public_key) || empty($router->vpn_ip)) {
            Log::warning("Router created without VPN details. WireGuard not updated.");
            return;
        }

        try {
            // 1. Connect across the internet to DigitalOcean
            $ssh = new SSH2(env('VPS_IP'));
            
            // Log in using the credentials from .env
            if (!$ssh->login(env('VPS_USERNAME'), env('VPS_PASSWORD'))) {
                throw new \Exception('Failed to authenticate with DigitalOcean via SSH.');
            }

            // 2. Execute the commands remotely on the DO VPS
            $addPeerCmd = "sudo wg set wg0 peer '{$router->wireguard_public_key}' allowed-ips '{$router->vpn_ip}/32'";
            $ssh->exec($addPeerCmd);
            
            $ssh->exec("sudo wg-quick save wg0");

            Log::info("Successfully deployed remote WireGuard peer for: {$router->name}");
            
            Notification::make()
                ->title('VPN Tunnel Configured')
                ->body('The router was successfully added to the DigitalOcean WireGuard network.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Remote WireGuard deployment failed for {$router->name}: " . $e->getMessage());
            
            Notification::make()
                ->title('VPN Tunnel Failed')
                ->body('Could not connect to DigitalOcean. Check system logs.')
                ->danger()
                ->send();
        }
    }
}
