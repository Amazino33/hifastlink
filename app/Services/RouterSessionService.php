<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Router;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouterSessionService
{
    /**
     * Force disconnect a user across all layers:
     * 1. Closes open radacct sessions (both remote RADIUS DB and local DB).
     * 2. Sends an RFC 3576 RADIUS Disconnect-Request (PoD) UDP packet to the NAS.
     * 3. Disconnects the active session via MikroTik REST/RouterOS API if configured.
     * 4. Marks matching devices offline.
     */
    public function forceDisconnectUser(string $username, ?string $mac = null, ?string $ip = null): array
    {
        $normalizedUser = strtolower(trim($username));
        $closedSessions = $this->closeRadAcctSessions($normalizedUser, $mac);

        // If filtering by MAC closed 0 rows (e.g. MAC was randomized or not sent by NAS),
        // close all active sessions for this username to ensure they are not locked out.
        if ($closedSessions === 0) {
            $closedSessions = $this->closeRadAcctSessions($normalizedUser);
        }

        $radiusPoDSent  = $this->sendRadiusDisconnect($normalizedUser);
        $routerApiSent  = $this->disconnectRouterApi($normalizedUser, $mac, $ip);

        // Update devices table
        if ($mac) {
            $cleanMac = strtoupper(str_replace(['-', '.'], ':', $mac));
            Device::where('mac', $cleanMac)->update([
                'is_connected' => false,
                'last_seen'    => now(),
            ]);
        }

        Log::info('RouterSessionService: Force disconnect executed', [
            'user'            => $normalizedUser,
            'mac'             => $mac,
            'ip'              => $ip,
            'closed_sessions' => $closedSessions,
            'radius_pod_sent' => $radiusPoDSent,
            'router_api_sent' => $routerApiSent,
        ]);

        return [
            'success'         => true,
            'closed_sessions' => $closedSessions,
            'radius_pod'      => $radiusPoDSent,
            'router_api'      => $routerApiSent,
        ];
    }

    /**
     * Close stale radacct sessions that haven't received an interim update recently.
     * Prevents FreeRADIUS Simultaneous-Use from rejecting legitimate reconnects.
     */
    public function closeStaleRadAcctSessions(string $username, int $staleMinutes = 2): int
    {
        $normalizedUser = strtolower(trim($username));
        $threshold = now()->subMinutes($staleMinutes);
        $totalClosed = 0;

        $connections = array_unique(array_filter([
            config('database.default'),
            'mysql',
            config('database.connections.radius') ? 'radius' : null,
        ]));

        foreach (array_unique($connections) as $conn) {
            try {
                $query = DB::connection($conn)->table('radacct')
                    ->whereRaw('LOWER(username) = ?', [$normalizedUser])
                    ->whereNull('acctstoptime')
                    ->where(function ($q) use ($threshold) {
                        $q->where('acctupdatetime', '<', $threshold)
                          ->orWhere(function ($q2) use ($threshold) {
                              $q2->whereNull('acctupdatetime')
                                 ->where('acctstarttime', '<', $threshold);
                          });
                    });

                $affected = $query->update([
                    'acctstoptime'       => now(),
                    'acctterminatecause' => 'Stale-AutoClean',
                ]);

                $totalClosed += $affected;
            } catch (\Throwable $e) {
                Log::warning("RouterSessionService: Failed closing stale radacct on connection [{$conn}]: " . $e->getMessage());
            }
        }

        return $totalClosed;
    }

    /**
     * Close open radacct sessions for a user, optionally filtered by MAC or IP.
     */
    public function closeRadAcctSessions(string $username, ?string $mac = null, ?string $ip = null): int
    {
        $normalizedUser = strtolower(trim($username));
        $totalClosed = 0;

        $connections = array_unique(array_filter([
            config('database.default'),
            'mysql',
            config('database.connections.radius') ? 'radius' : null,
        ]));

        foreach ($connections as $conn) {
            try {
                $query = DB::connection($conn)->table('radacct')
                    ->whereRaw('LOWER(username) = ?', [$normalizedUser])
                    ->whereNull('acctstoptime');

                if ($mac) {
                    $cleanMac = strtoupper(str_replace(['-', '.'], ':', $mac));
                    $macHex = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
                    $query->where(function ($mq) use ($cleanMac, $macHex) {
                        $mq->where('callingstationid', $cleanMac)
                           ->orWhereRaw("UPPER(REPLACE(REPLACE(callingstationid,':',''),'-','')) = ?", [$macHex]);
                    });
                }

                if ($ip) {
                    $query->where('framedipaddress', $ip);
                }

                $affected = $query->update([
                    'acctstoptime'       => now(),
                    'acctterminatecause' => 'Force-Reset',
                ]);

                $totalClosed += $affected;
            } catch (\Throwable $e) {
                Log::warning("RouterSessionService: Failed closing radacct on connection [{$conn}]: " . $e->getMessage());
            }
        }

        return $totalClosed;
    }

    /**
     * Send a RADIUS Disconnect-Request (RFC 3576 / PoD) via UDP port 3799.
     * This commands the router to immediately drop the user's active session.
     */
    public function sendRadiusDisconnect(string $username, int $timeoutSeconds = 2): bool
    {
        $server = config('services.radius.server') ?? env('RADIUS_SERVER') ?? env('RADIUS_DB_HOST') ?? '142.93.47.189';
        $secret = config('services.radius.secret') ?? env('RADIUS_SECRET') ?? 'testing123';
        $port   = (int) (config('services.radius.disconnect_port') ?? env('RADIUS_DISCONNECT_PORT', 3799));

        if (! $server || ! $secret) {
            Log::info('RouterSessionService: RADIUS Disconnect skipped (server or secret not configured)');
            return false;
        }

        try {
            $id       = rand(0, 255);
            $userAttr = chr(1) . chr(2 + strlen($username)) . $username;
            $length   = 20 + strlen($userAttr);
            $header   = pack('CCn', 40, $id, $length) . str_repeat("\x00", 16);
            $auth     = md5($header . $userAttr . $secret, true);
            $packet   = pack('CCn', 40, $id, $length) . $auth . $userAttr;

            $sock = @fsockopen("udp://{$server}", $port, $errno, $errstr, $timeoutSeconds);
            if (! $sock) {
                Log::warning('RouterSessionService: RADIUS UDP socket failed', ['errno' => $errno, 'errstr' => $errstr]);
                return false;
            }

            stream_set_timeout($sock, $timeoutSeconds);
            fwrite($sock, $packet);
            $resp = @fread($sock, 256);
            fclose($sock);

            if ($resp && strlen($resp) > 0 && ord($resp[0]) === 41) {
                Log::info('RouterSessionService: RADIUS Disconnect-ACK received', ['user' => $username]);
                return true;
            }

            Log::info('RouterSessionService: RADIUS Disconnect-NAK or no response', ['user' => $username]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('RouterSessionService: RADIUS disconnect error', ['user' => $username, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Disconnect active session from MikroTik router via REST API.
     */
    public function disconnectRouterApi(string $username, ?string $mac = null, ?string $ip = null): bool
    {
        $normalizedUser = strtolower(trim($username));

        // 1. Try to find the router from the latest radacct row
        $session = null;
        $connections = ['mysql'];
        if (config('database.connections.radius')) {
            $connections[] = 'radius';
        }

        foreach ($connections as $conn) {
            try {
                $session = DB::connection($conn)->table('radacct')
                    ->whereRaw('LOWER(username) = ?', [$normalizedUser])
                    ->orderByDesc('acctstarttime')
                    ->first();
                if ($session) break;
            } catch (\Throwable) {}
        }

        $router = null;
        if ($session) {
            if (! empty($session->nasipaddress)) {
                $router = Router::where('ip_address', $session->nasipaddress)->first();
            }
            if (! $router && ! empty($session->nas_identifier)) {
                $router = Router::where('nas_identifier', $session->nas_identifier)->first();
            }
            if (! $router && ! empty($session->calledstationid)) {
                $router = Router::where('nas_identifier', $session->calledstationid)->first();
            }
        }

        // Fallback to default router config from environment if none matched
        $host = $router?->ip_address ?? env('MIKROTIK_API_HOST');
        $user = $router?->api_user ?? env('MIKROTIK_API_USER');
        $pass = $router?->api_password ?? env('MIKROTIK_API_PASSWORD');
        $port = (int) ($router?->api_port ?? env('MIKROTIK_API_PORT', 80));

        // If port is the binary RouterOS port 8728, REST API runs on port 80/443
        if ($port === 8728) {
            $port = 80;
        }

        if (! $host || ! $user || ! $pass) {
            Log::info('RouterSessionService: MikroTik REST API skipped (no credentials)');
            return false;
        }

        $base   = 'http://' . $host . ($port && $port !== 80 ? ":{$port}" : '');
        $client = new Client(['timeout' => 3, 'verify' => false, 'http_errors' => false]);

        try {
            $response = $client->get("{$base}/rest/ip/hotspot/active", [
                'auth' => [$user, $pass],
            ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $sessions = json_decode($response->getBody()->getContents(), true) ?: [];
            $kicked = false;

            foreach ($sessions as $s) {
                $userMatch = strcasecmp($s['user'] ?? '', $normalizedUser) === 0;
                $macMatch  = $mac ? strcasecmp(str_replace(['-', '.'], ':', $s['mac-address'] ?? ''), str_replace(['-', '.'], ':', $mac)) === 0 : true;
                $ipMatch   = $ip ? ($s['address'] ?? '') === $ip : true;

                if ($userMatch && ($macMatch || $ipMatch) && isset($s['.id'])) {
                    $client->delete("{$base}/rest/ip/hotspot/active/{$s['.id']}", [
                        'auth' => [$user, $pass],
                    ]);
                    $kicked = true;
                    Log::info('RouterSessionService: Session removed via REST API', [
                        'user'    => $normalizedUser,
                        'session' => $s['.id'],
                    ]);
                }
            }

            return $kicked;
        } catch (\Throwable $e) {
            Log::warning('RouterSessionService: MikroTik REST API error', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
