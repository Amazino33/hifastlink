<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NetworkController extends Controller
{
    public function disconnectUser(Request $request, User $user)
    {
        // Send RADIUS Disconnect-Request
        $result = $this->sendRadiusDisconnect($user->username);

        if ($result) {
            $user->update(['connection_status' => 'disconnected']);
            Log::info("User {$user->username} disconnected by admin");
            return back()->with('success', 'User disconnected successfully');
        }

        return back()->with('error', 'Failed to disconnect user');
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

    private function sendRadiusDisconnect(string $username): bool
    {
        try {
            // You'll need to install pear/net_radius
            // composer require pear/net_radius

            $radius = new \Net_RADIUS(config('services.radius.server'), config('services.radius.secret'), 1812);
            $radius->addAttribute('User-Name', $username);
            $radius->addAttribute('Acct-Session-Id', 'admin_disconnect');

            $result = $radius->sendRequest(\Net_RADIUS::DISCONNECT_REQUEST);
            return $result === \Net_RADIUS::DISCONNECT_ACK;
        } catch (\Exception $e) {
            Log::error('RADIUS disconnect failed', ['user' => $username, 'error' => $e->getMessage()]);
            return false;
        }
    }
}