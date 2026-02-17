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
        $mac = session('current_device_mac') ?? $request->input('mac');

        $updateData = [
            'acctstoptime' => now(),
            'acctterminatecause' => 'Admin-Reset',
        ];

        // Local force-close helper to prevent zombie sessions when router is unreachable
        $forceCloseSession = function () use ($username, $mac, $updateData) {
            $query = DB::table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime');

            if ($mac) {
                $query->where('callingstationid', $mac);
            }

            $query->update($updateData);
        };

        $timeoutSeconds = (int) (config('services.radius.disconnect_timeout', 3));

        try {
            // Attempt CoA/Disconnect to the router with a short timeout
            $this->sendRadiusDisconnect($username, $timeoutSeconds);

            // Best-effort direct router disconnect using stored API credentials
            $this->disconnectFromRouter($username, $mac);

            // Ensure local cleanup even if router does not write back
            DB::table('radacct')
                ->where('username', $username)
                ->when($mac, fn ($q) => $q->where('callingstationid', $mac))
                ->whereNull('acctstoptime')
                ->update($updateData);
        } catch (\Throwable $e) {
            Log::error('Router unreachable, forcing DB close', [
                'user' => $username,
                'mac' => $mac,
                'error' => $e->getMessage(),
            ]);

            // Still try router-side disconnect before falling back to DB only
            try {
                $this->disconnectFromRouter($username, $mac);
            } catch (\Throwable $inner) {
                Log::warning('Direct router disconnect failed', [
                    'user' => $username,
                    'mac' => $mac,
                    'error' => $inner->getMessage(),
                ]);
            }

            DB::table('radacct')
                ->where('username', $username)
                ->when($mac, fn ($q) => $q->where('callingstationid', $mac))
                ->whereNull('acctstoptime')
                ->update($updateData);
        }

        $user->update(['connection_status' => 'disconnected']);

        // Immediately mark Device entries as disconnected so the dashboard updates instantly
        if ($mac) {
            Device::where('user_id', $user->id)->where('mac', $mac)->update(['is_connected' => false, 'last_seen' => now()]);
            if (session('current_device_mac') === $mac) {
                session()->forget('current_device_mac');
                Cookie::queue(Cookie::forget('fastlink_device_token'));
            }
        } else {
            // No MAC provided -> mark all user's devices as disconnected
            Device::where('user_id', $user->id)->where('is_connected', true)->update(['is_connected' => false, 'last_seen' => now()]);
            session()->forget('current_device_mac');
            Cookie::queue(Cookie::forget('fastlink_device_token'));
        }

        // Ensure the devices table is in sync immediately
        try {
            Artisan::call('radius:sync-devices');
            Log::info('NetworkController: radius:sync-devices called after disconnect');
        } catch (\Exception $e) {
            Log::warning('NetworkController: radius:sync-devices failed: ' . $e->getMessage());
        }

        // For browser form posts, redirect back with a flash message so the UI updates naturally
        if (! $request->wantsJson()) {
            return redirect()->back()->with('success', 'Disconnected successfully');
        }

        return response()->json(['message' => 'Disconnected successfully', 'status' => 'offline']);
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

    private function sendRadiusDisconnect(string $username, int $timeoutSeconds = 3): bool
    {
        try {
            // You'll need to install pear/net_radius
            // composer require pear/net_radius

            $radius = new \Net_RADIUS(config('services.radius.server'), config('services.radius.secret'), 1812);
            $radius->addAttribute('User-Name', $username);
            $radius->addAttribute('Acct-Session-Id', 'admin_disconnect');

            // If the library supports it, set a short timeout (best-effort)
            if (method_exists($radius, 'setOption')) {
                $radius->setOption('timeout', max(1, $timeoutSeconds));
            }

            // Fallback: rely on PHP socket timeout already set by caller

            $result = $radius->sendRequest(\Net_RADIUS::DISCONNECT_REQUEST);
            return $result === \Net_RADIUS::DISCONNECT_ACK;
        } catch (\Exception $e) {
            Log::error('RADIUS disconnect failed', ['user' => $username, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Direct MikroTik disconnect using Router REST API (best-effort)
     */
    private function disconnectFromRouter(string $username, ?string $mac = null): void
    {
        $session = RadAcct::forUser($username)
            ->active()
            ->when($mac, fn ($q) => $q->where('callingstationid', $mac))
            ->latest('acctstarttime')
            ->first();

        if (! $session) {
            return;
        }

        $router = Router::where('ip_address', $session->nasipaddress)
            ->orWhere('nas_identifier', $session->nas_identifier ?? $session->calledstationid)
            ->first();

        if (! $router || ! $router->api_user || ! $router->api_password) {
            Log::info('Router API credentials missing; skipping direct disconnect', [
                'user' => $username,
                'router_ip' => $session->nasipaddress,
                'nas_identifier' => $session->nas_identifier,
            ]);
            return;
        }

        $host = $router->ip_address ?? $router->nas_identifier;
        $port = $router->api_port ?: 80;
        $base = "http://{$host}" . ($port && $port !== 80 ? ":{$port}" : '');

        $client = new Client([
            'timeout' => 5,
            'verify' => false,
        ]);

        try {
            $response = $client->get("{$base}/rest/ip/hotspot/active", [
                'auth' => [$router->api_user, $router->api_password],
            ]);

            $sessions = json_decode($response->getBody()->getContents(), true) ?: [];

            foreach ($sessions as $s) {
                $userMatch = ($s['user'] ?? null) === $username;
                $macMatch = $mac ? strcasecmp($s['mac-address'] ?? '', $mac) === 0 : true;

                if ($userMatch && $macMatch) {
                    $id = $s['.id'] ?? null;
                    if (! $id) {
                        continue;
                    }

                    $client->delete("{$base}/rest/ip/hotspot/active/{$id}", [
                        'auth' => [$router->api_user, $router->api_password],
                    ]);

                    Log::info('Router REST disconnect sent', [
                        'user' => $username,
                        'mac' => $mac,
                        'router' => $router->id,
                        'session_id' => $id,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Router REST disconnect failed', [
                'user' => $username,
                'mac' => $mac,
                'router' => $router->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}