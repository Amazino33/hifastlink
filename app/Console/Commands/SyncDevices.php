<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RadAcct;
use App\Models\Device;
use App\Models\User;
use App\Models\Router;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncDevices extends Command
{
    protected $signature = 'radius:sync-devices';
    protected $description = 'Sync Device.is_connected and last_seen from RADIUS (radacct) active sessions';

    public function handle(): int
    {
        $this->info('Starting devices sync from RadAcct...');

        $activeSessions = RadAcct::whereNull('acctstoptime')->get();

        // Keep track of which (user,mac) pairs are active
        $activePairs = [];

        foreach ($activeSessions as $sess) {
            $username = $sess->username;
            $mac = $sess->callingstationid ?? null;

            if (! $username || ! $mac) {
                continue;
            }

            $user = User::where('username', $username)->first();
            if (! $user) {
                continue;
            }

            $routerId = null;
            $nas = $sess->nasidentifier ?: $sess->nasipaddress ?: null;
            if ($nas) {
                $router = Router::where('nas_identifier', $nas)->orWhere('ip_address', $nas)->first();
                $routerId = $router?->id;
            }

            // Upsert device entry
            try {
                $device = Device::firstOrNew(['user_id' => $user->id, 'mac' => $mac]);
                $device->ip = $sess->framedipaddress ?? $sess->nasipaddress ?? $device->ip;
                $device->user_agent = $device->user_agent ?? null;
                $device->router_id = $routerId ?: $device->router_id;
                $device->last_seen = now();
                $device->is_connected = true;
                $device->save();

                $activePairs[$user->id . '|' . $mac] = true;
            } catch (\Throwable $e) {
                Log::warning('Failed to upsert device from radacct: ' . $e->getMessage(), ['username' => $username, 'mac' => $mac]);
            }
        }

        // Mark devices as disconnected if they are not present in activePairs
        $devices = Device::where('is_connected', true)->get();
        foreach ($devices as $dev) {
            $key = $dev->user_id . '|' . $dev->mac;
            if (! isset($activePairs[$key])) {
                // Not active in radacct -> mark disconnected
                $dev->is_connected = false;
                $dev->save();
            }
        }

        $this->info('Devices sync completed. Active sessions: ' . count($activePairs));
        return 0;
    }
}
