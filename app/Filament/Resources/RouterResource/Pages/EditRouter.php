<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use App\Models\Router;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class EditRouter extends EditRecord
{
    protected static string $resource = RouterResource::class;

    /**
     * Before saving, check if the router is missing keys or VPN IP.
     * If so, generate them fresh (handles old routers created before this logic existed).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $router = $this->record;

        // Only generate new keys if this router doesn't have them yet
        $needsKeys = empty($router->wireguard_private_key) || empty($router->wireguard_public_key);
        $needsVpnIp = empty($router->vpn_ip);

        if (!$needsKeys && !$needsVpnIp) {
            // Nothing to generate, pass through as-is
            return $data;
        }

        try {
            $ssh = new SSH2(config('services.digitalocean.ip'));
            if (! $ssh->login(config('services.digitalocean.user'), config('services.digitalocean.pass'))) {
                throw new \Exception('Failed to authenticate with DigitalOcean via SSH.');
            }

            // Generate WireGuard keys if missing
            if ($needsKeys) {
                $privateKey = trim($ssh->exec('wg genkey'));
                $publicKey  = trim($ssh->exec("echo '{$privateKey}' | wg pubkey"));

                if (empty($privateKey) || empty($publicKey)) {
                    throw new \Exception('DigitalOcean returned empty WireGuard keys.');
                }

                $data['wireguard_private_key'] = $privateKey;
                $data['wireguard_public_key']  = $publicKey;

                Log::info("Generated new WireGuard keys for router: {$router->name}");
            }

            // Auto-assign next available VPN IP if missing
            if ($needsVpnIp) {
                $usedIps = Router::whereNotNull('vpn_ip')->pluck('vpn_ip')->toArray();

                // Scan 192.168.42.2 - 192.168.42.254 for next free IP
                // (.1 is the server, .11+ are routers — adjust range as needed)
                $assignedIp = null;
                for ($i = 11; $i <= 254; $i++) {
                    $candidate = "192.168.42.{$i}";
                    if (! in_array($candidate, $usedIps)) {
                        $assignedIp = $candidate;
                        break;
                    }
                }

                if (! $assignedIp) {
                    throw new \Exception('VPN IP pool exhausted. No available IPs in 192.168.42.0/24.');
                }

                $data['vpn_ip'] = $assignedIp;

                Log::info("Assigned VPN IP {$assignedIp} to router: {$router->name}");
            }

        } catch (\Exception $e) {
            Log::error("Key/IP generation failed for router {$router->name}: " . $e->getMessage());

            Notification::make()
                ->title('Key Generation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $data;
    }

    /**
     * After saving, sync the router's WireGuard peer and FreeRADIUS client config
     * on the DigitalOcean server. Safe to run on every save — it cleans up old
     * entries before re-adding, so no duplicates.
     */
    protected function afterSave(): void
    {
        $router = $this->record->fresh(); // Fresh from DB to get any new keys/IP we just saved

        if (empty($router->wireguard_public_key) || empty($router->vpn_ip)) {
            Log::warning("Skipping WireGuard sync for {$router->name} — missing keys or VPN IP.");
            return;
        }

        try {
            $ssh = new SSH2(config('services.digitalocean.ip'));
            if (! $ssh->login(config('services.digitalocean.user'), config('services.digitalocean.pass'))) {
                throw new \Exception('Failed to authenticate with DigitalOcean via SSH.');
            }

            $vpnIp  = $router->vpn_ip;
            $pubKey = $router->wireguard_public_key;
            $secret = $router->secret;
            $name   = $router->nas_identifier ?: $router->name;

            // -------------------------------------------------------
            // 1. WireGuard — remove old peer entry, re-add fresh
            // -------------------------------------------------------

            // Remove from live interface (silently fails if not present)
            $ssh->exec("sudo wg set wg0 peer '{$pubKey}' remove 2>/dev/null || true");

            // Remove old block from wg0.conf by matching the comment line we always write
            $ssh->exec("sudo sed -i '/^# {$name}$/,/^$/d' /etc/wireguard/wg0.conf");

            // Append fresh peer block to wg0.conf (persists across reboots)
            $peerBlock = "# {$name}\n[Peer]\nPublicKey = {$pubKey}\nAllowedIPs = {$vpnIp}/32";
            $ssh->exec("printf '\n{$peerBlock}\n' | sudo tee -a /etc/wireguard/wg0.conf > /dev/null");

            // Hot-add the peer to the live interface (no restart needed)
            $ssh->exec("sudo wg set wg0 peer '{$pubKey}' allowed-ips '{$vpnIp}/32'");

            Log::info("WireGuard peer synced for: {$router->name} ({$vpnIp})");

            // -------------------------------------------------------
            // 2. FreeRADIUS — overwrite clients.d file for this router
            // -------------------------------------------------------

            $clientConf = "client {$name} {\n    ipaddr = {$vpnIp}\n    secret = {$secret}\n    nas_type = other\n}\n";
            $ssh->exec("echo '{$clientConf}' | sudo tee /etc/freeradius/3.0/clients.d/{$name}.conf > /dev/null");
            $ssh->exec("sudo systemctl reload freeradius");

            Log::info("FreeRADIUS client config updated for: {$router->name}");

            Notification::make()
                ->title('Router Synced')
                ->body("WireGuard and RADIUS configs updated for {$router->name}.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("Router sync failed for {$router->name}: " . $e->getMessage());

            Notification::make()
                ->title('Sync Failed')
                ->body('Could not update WireGuard or RADIUS config. Check system logs.')
                ->danger()
                ->send();
        }
    }
}