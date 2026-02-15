<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RadAcct;
use App\Models\Device;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;

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
}