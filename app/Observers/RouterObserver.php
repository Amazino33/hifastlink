<?php

namespace App\Observers;

use App\Models\Router;
use App\Models\Nas;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class RouterObserver
{
    /**
     * Handle the Router "created" event.
     */
    public function created(Router $router): void
    {
        $this->syncToRadiusNas($router);
    }

    /**
     * Handle the Router "updated" event.
     */
    public function updated(Router $router): void
    {
        $this->syncToRadiusNas($router);
    }

    /**
     * Handle the Router "deleted" event.
     */
    public function deleted(Router $router): void
    {
        $nasIp = $router->vpn_ip ?: $router->ip_address;
        Nas::where('nasname', $nasIp)->delete();

        Log::info("Removed router from RADIUS NAS table", [
            'router' => $router->name,
            'ip' => $nasIp,
        ]);

        if (! app()->isProduction()) {
            Log::info("Skipping SSH cleanup for router [{$router->name}] in non-production environment.");
            return;
        }

        $name   = $router->nas_identifier ?: $router->name;
        $pubKey = $router->wireguard_public_key;

        try {
            $ssh = new SSH2(config('services.digitalocean.ip'));
            if (! $ssh->login(config('services.digitalocean.user'), config('services.digitalocean.pass'))) {
                throw new \Exception('SSH authentication failed.');
            }

            // Remove WireGuard peer from live interface
            if ($pubKey) {
                $ssh->exec("sudo wg set wg0 peer '{$pubKey}' remove 2>/dev/null || true");
                $ssh->exec("sudo sed -i '/^# {$name}$/,/^$/d' /etc/wireguard/wg0.conf");
            }

            // Remove FreeRADIUS client config file and reload
            $ssh->exec("sudo rm -f /etc/freeradius/3.0/clients.d/{$name}.conf");
            $ssh->exec("sudo systemctl restart freeradius");

            Log::info("Cleaned up server config for deleted router [{$name}]");

        } catch (\Exception $e) {
            Log::error("Failed to clean up server config for router [{$name}]: " . $e->getMessage());
        }
    }

    /**
     * Sync router to RADIUS nas table.
     * RADIUS traffic arrives over the WireGuard VPN, so FreeRADIUS sees the
     * source IP as vpn_ip — that must be what we register as nasname.
     */
    protected function syncToRadiusNas(Router $router): void
    {
        // vpn_ip is the WireGuard tunnel IP — always the source of RADIUS packets
        $nasIp = $router->vpn_ip ?: $router->ip_address;

        // If the router previously had a stale entry under a different IP, remove it
        Nas::where('shortname', $router->nas_identifier)
            ->where('nasname', '!=', $nasIp)
            ->delete();

        Nas::updateOrCreate(
            ['nasname' => $nasIp],
            [
                'shortname'   => $router->nas_identifier,
                'type'        => 'other',
                'ports'       => 1812,
                'secret'      => $router->secret,
                'server'      => null,
                'community'   => null,
                'description' => $router->name . ' - ' . $router->location,
            ]
        );

        Log::info("Synced router to RADIUS NAS table", [
            'router'         => $router->name,
            'nas_ip'         => $nasIp,
            'nas_identifier' => $router->nas_identifier,
        ]);
    }
}
