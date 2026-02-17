<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RadAcct;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cookie;
use GuzzleHttp\Client;
use App\Models\Router;

class NetworkController extends Controller
{
    public function disconnectUser(Request $request, User $user = null)
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $username = $user->username;

        // Normalize MAC to uppercase colon-separated (MikroTik format) for router REST calls.
        // We do NOT use this to filter radacct rows — see Step 1.
        $rawMac = session('current_device_mac') ?? $request->input('mac');
        $mac    = $rawMac ? strtoupper(str_replace(['-', '.'], ':', $rawMac)) : null;

        $updateData = [
            'acctstoptime'       => now(),
            'acctterminatecause' => 'User-Request',
        ];

        // ── Step 1: Close ALL open radacct sessions for this user (no MAC filter).
        //
        // MAC format in callingstationid ("AA:BB:CC:DD:EE:FF") often differs from the
        // value stored in the app session ("aa-bb-cc-dd-ee-ff"), so a MAC-based WHERE
        // silently matches zero rows and leaves acctstoptime NULL.
        $affected = DB::table('radacct')
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->whereNull('acctstoptime')
            ->update($updateData);

        Log::info('NetworkController: radacct rows closed', [
            'user'     => $username,
            'mac_hint' => $mac,
            'rows'     => $affected,
        ]);

        // ── Step 2: Hit the MikroTik REST API to drop the live session on the NAS.
        try {
            $this->disconnectFromRouter($username, $mac);
        } catch (\Throwable $e) {
            Log::warning('NetworkController: router REST disconnect failed', [
                'user'  => $username,
                'mac'   => $mac,
                'error' => $e->getMessage(),
            ]);
        }

        // ── Step 3: Send a RADIUS Disconnect-Request (RFC 3576) as an additional NAS signal.
        try {
            $this->sendRadiusDisconnect($username);
        } catch (\Throwable $e) {
            Log::warning('NetworkController: RADIUS disconnect failed', ['user' => $username, 'error' => $e->getMessage()]);
        }

        // ── Step 4: Mark devices offline and clear session/cookie.
        Device::where('user_id', $user->id)
            ->where('is_connected', true)
            ->update(['is_connected' => false, 'last_seen' => now()]);

        session()->forget('current_device_mac');
        Cookie::queue(Cookie::forget('fastlink_device_token'));

        $user->update(['connection_status' => 'disconnected']);

        try {
            Artisan::call('radius:sync-devices');
        } catch (\Exception $e) {
            Log::warning('NetworkController: radius:sync-devices failed: ' . $e->getMessage());
        }

        // Build the hotspot logout URL and include it in the JSON response
        // so the browser can navigate to login.wifi/logout to close its captive session.
        $gateway   = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';
        if (strpos($gateway, '://') === false) {
            $gateway = 'http://' . $gateway;
        }
        $parsed    = parse_url($gateway);
        $logoutUrl = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'login.wifi') . '/logout';

        if (! $request->wantsJson()) {
            return redirect($logoutUrl);
        }

        return response()->json([
            'message'    => 'Disconnected successfully',
            'status'     => 'offline',
            'logout_url' => $logoutUrl,
        ]);
    }

    public function suspendUser(Request $request, User $user)
    {
        $user->update(['connection_status' => 'suspended']);

        // Sync to RADIUS to block user
        \Artisan::call('radius:sync-users');

        Log::info("User {$user->username} suspended by admin");
        return back()->with('success', 'User suspended successfully');
    }

    public function activateUser(Request $request, User $user)
    {
        $user->update(['connection_status' => 'active']);

        // Sync to RADIUS to allow user
        \Artisan::call('radius:sync-users');

        Log::info("User {$user->username} activated by admin");
        return back()->with('success', 'User activated successfully');
    }

    /**
     * Send a RADIUS Disconnect-Request (RFC 3576) via raw UDP — no PECL/PEAR needed.
     */
    private function sendRadiusDisconnect(string $username, int $timeoutSeconds = 3): bool
    {
        $server = config('services.radius.server');
        $secret = config('services.radius.secret');
        $port   = (int) config('services.radius.disconnect_port', 3799);

        if (! $server || ! $secret) {
            Log::info('RADIUS disconnect skipped — server/secret not configured');
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
                Log::warning('RADIUS UDP socket failed', ['errno' => $errno, 'errstr' => $errstr]);
                return false;
            }

            stream_set_timeout($sock, $timeoutSeconds);
            fwrite($sock, $packet);
            $resp = @fread($sock, 256);
            fclose($sock);

            if ($resp && strlen($resp) > 0 && ord($resp[0]) === 41) {
                Log::info('RADIUS Disconnect-ACK', ['user' => $username]);
                return true;
            }

            Log::info('RADIUS Disconnect-NAK or no response', ['user' => $username]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('RADIUS disconnect error', ['user' => $username, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Drop the active MikroTik hotspot session via the router's REST API.
     */
    private function disconnectFromRouter(string $username, ?string $mac = null): void
    {
        // Find our just-closed session (or latest) to determine which router the user was on
        $session = DB::table('radacct')
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->orderByDesc('acctstarttime')
            ->first();

        if (! $session) {
            Log::info('disconnectFromRouter: no radacct row for user', ['user' => $username]);
            return;
        }

        $router = null;
        if (! empty($session->nasipaddress)) {
            $router = Router::where('ip_address', $session->nasipaddress)->first();
        }
        if (! $router && ! empty($session->nas_identifier)) {
            $router = Router::where('nas_identifier', $session->nas_identifier)->first();
        }
        if (! $router && ! empty($session->calledstationid)) {
            $router = Router::where('nas_identifier', $session->calledstationid)->first();
        }

        if (! $router || ! $router->api_user || ! $router->api_password) {
            Log::info('disconnectFromRouter: router not found or missing API credentials', [
                'user'         => $username,
                'nasipaddress' => $session->nasipaddress ?? null,
            ]);
            return;
        }

        $host   = $router->ip_address ?? $router->nas_identifier;
        $port   = $router->api_port ?: 80;
        $base   = 'http://' . $host . ($port && $port !== 80 ? ":{$port}" : '');
        $client = new Client(['timeout' => 5, 'verify' => false]);

        try {
            $response = $client->get("{$base}/rest/ip/hotspot/active", [
                'auth' => [$router->api_user, $router->api_password],
            ]);

            $sessions = json_decode($response->getBody()->getContents(), true) ?: [];

            foreach ($sessions as $s) {
                $userMatch = strcasecmp($s['user'] ?? '', $username) === 0;
                $macMatch  = $mac ? strcasecmp($s['mac-address'] ?? '', $mac) === 0 : true;

                if ($userMatch && $macMatch && isset($s['.id'])) {
                    $client->delete("{$base}/rest/ip/hotspot/active/{$s['.id']}", [
                        'auth' => [$router->api_user, $router->api_password],
                    ]);

                    Log::info('disconnectFromRouter: session removed via REST', [
                        'user'      => $username,
                        'mac'       => $mac,
                        'router_id' => $router->id,
                        'session'   => $s['.id'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('disconnectFromRouter: REST call failed', [
                'user'      => $username,
                'router_id' => $router->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}