<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class CreateRouter extends CreateRecord
{
    protected static string $resource = RouterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            // 1. Connect to DigitalOcean
            $ssh = new SSH2(config('services.digitalocean.ip'));
            if (! $ssh->login(config('services.digitalocean.user'), config('services.digitalocean.pass'))) {
                throw new \Exception('Failed to authenticate with DigitalOcean via SSH.');
            }

            // 2. Ask Ubuntu to generate a mathematically valid Private Key
            $privateKey = trim($ssh->exec('wg genkey'));

            // 3. Ask Ubuntu to generate the matching Public Key
            $publicKey = trim($ssh->exec("echo '{$privateKey}' | wg pubkey"));

            if (empty($privateKey) || empty($publicKey)) {
                throw new \Exception('DigitalOcean returned empty WireGuard keys.');
            }

            // 4. Inject these keys into the data array so Laravel saves them to the database
            $data['wireguard_private_key'] = $privateKey;
            $data['wireguard_public_key'] = $publicKey;

        } catch (\Exception $e) {
            Log::error('Key Generation Failed: '.$e->getMessage());
            // You can throw an exception here to halt creation if you want it to strictly fail
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // $this->record contains the newly created Router model from the database
        $router = $this->record;

        // Ensure we have the required data before running server commands
        if (empty($router->wireguard_public_key) || empty($router->vpn_ip)) {
            Log::warning('Router created without VPN details. WireGuard not updated.');

            return;
        }

        try {
            // 1. Establish the secure SSH connection
            $ssh = new SSH2(config('services.digitalocean.ip'));

            if (! $ssh->login(config('services.digitalocean.user'), config('services.digitalocean.pass'))) {
                throw new \Exception('Failed to authenticate with DigitalOcean via SSH.');
            }

            // Register router IP in FreeRADIUS clients.d
            $vpnIp = $router->vpn_ip;
            $pubKey = $router->wireguard_public_key;
            $secret = $router->secret;
            $name = $router->nas_identifier ?: $router->name;

            // 2. Execute the command and capture the output
            $peerBlock = "\n# {$name}\n[Peer]\nPublicKey = {$pubKey}\nAllowedIPs = {$vpnIp}/32\n";

            // Append peer to wg0.conf
            $ssh->exec("echo '{$peerBlock}' | sudo tee -a /etc/wireguard/wg0.conf > /dev/null");

            // Hot-add the peer to the live interface (no restart needed)
            $ssh->exec("sudo wg set wg0 peer '{$pubKey}' allowed-ips '{$vpnIp}/32'");

            $clientConf = "client {$name} {\n    ipaddr = {$vpnIp}\n    secret = {$secret}\n    nas_type = other\n}\n";
            $ssh->exec("echo '{$clientConf}' | sudo tee /etc/freeradius/3.0/clients.d/{$name}.conf > /dev/null");
            $ssh->exec("sudo systemctl reload freeradius");

            Log::info("Successfully deployed remote WireGuard peer for: {$router->name}");

            Notification::make()
                ->title('VPN Tunnel Configured')
                ->body('The router was successfully added to the DigitalOcean WireGuard network.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            // This will now catch the silent terminal errors!
            Log::error("Remote WireGuard deployment failed for {$router->name}: ".$e->getMessage());

            Notification::make()
                ->title('VPN Tunnel Failed')
                ->body('Could not configure WireGuard. Check system logs.')
                ->danger()
                ->send();
        }
    }
}
