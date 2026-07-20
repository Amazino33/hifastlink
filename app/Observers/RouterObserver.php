<?php

namespace App\Observers;

use App\Models\Router;
use App\Models\Nas;
use Illuminate\Support\Facades\Log;

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
