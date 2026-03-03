<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

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

        // 1. Inject the new router into the live WireGuard memory (Zero Downtime)
        $addPeer = Process::run("sudo wg set wg0 peer '{$router->wireguard_public_key}' allowed-ips '{$router->vpn_ip}/32'");

        if ($addPeer->successful()) {
            
            // 2. Save the live memory to the wg0.conf file permanently
            Process::run("sudo wg-quick save wg0");
            
            Log::info("Successfully deployed WireGuard peer for: {$router->name}");
            
            // Show a nice green success toast in the Filament dashboard
            Notification::make()
                ->title('VPN Tunnel Configured')
                ->body('The router was successfully added to the live WireGuard network.')
                ->success()
                ->send();

        } else {
            // If it fails, log the exact Ubuntu error so you can debug it
            Log::error("WireGuard deployment failed for {$router->name}: " . $addPeer->errorOutput());
            
            Notification::make()
                ->title('VPN Tunnel Failed')
                ->body('Could not add the router to WireGuard. Check system logs.')
                ->danger()
                ->send();
        }
    }
}
