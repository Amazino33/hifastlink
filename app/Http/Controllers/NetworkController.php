<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RadAcct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NetworkController extends Controller
{
    public function disconnectUser(Request $request, User $user)
    {
        // Identify the active session (optionally scoped to a specific MAC)
        $activeSessionQuery = RadAcct::forUser($user->username)
            ->whereNull('acctstoptime');

        if ($request->filled('mac')) {
            $activeSessionQuery->where('callingstationid', $request->input('mac'));
        }

        $activeSession = $activeSessionQuery
            ->orderByDesc('acctstarttime')
            ->first();

        if (! $activeSession) {
            // Nothing to disconnect; still return success so UI can flip back
            $user->update(['connection_status' => 'disconnected']);
            return back()->with('success', 'Disconnected successfully');
        }

        $timeoutSeconds = (int) (config('services.radius.disconnect_timeout', 3));

        $coaSucceeded = false;
        $previousTimeout = ini_get('default_socket_timeout');

        try {
            // Short socket timeout to avoid UI hanging on dead routers
            if ($timeoutSeconds > 0) {
                ini_set('default_socket_timeout', (string) $timeoutSeconds);
            }

            $coaSucceeded = $this->sendRadiusDisconnect($user->username, $timeoutSeconds);
        } catch (\Throwable $e) {
            Log::warning('Router unreachable during disconnect; forcing session close', [
                'user' => $user->username,
                'mac' => $request->input('mac'),
                'error' => $e->getMessage(),
            ]);
            $coaSucceeded = false;
        } finally {
            // Restore previous timeout
            if ($previousTimeout !== false && $previousTimeout !== null) {
                ini_set('default_socket_timeout', (string) $previousTimeout);
            }
        }

        // Always mark the session as closed to avoid zombie sessions
        $activeSessionQuery->update([
            'acctstoptime' => now(),
            'acctterminatecause' => $coaSucceeded ? 'User-Request' : 'Admin-Reset',
        ]);

        $user->update(['connection_status' => 'disconnected']);

        return back()->with('success', 'Disconnected successfully');
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