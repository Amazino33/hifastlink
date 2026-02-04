<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RadiusController extends Controller
{
    public function handleAccounting(Request $request)
    {
        $data = $request->all();

        Log::info('RADIUS Accounting Packet Received', $data);

        // Basic validation - you should add more security here
        if (!$this->validateAccountingRequest($request)) {
            Log::warning('Invalid RADIUS accounting request', $data);
            return response()->json(['status' => 'error'], 400);
        }

        $username = $data['User-Name'] ?? null;

        if (!$username) {
            Log::warning('No username in accounting packet', $data);
            return response()->json(['status' => 'error'], 400);
        }

        $user = User::where('username', $username)->first();

        if (!$user) {
            Log::warning("Accounting for unknown user: {$username}", $data);
            return response()->json(['status' => 'ok']); // Don't fail for unknown users
        }

        $statusType = $data['Acct-Status-Type'] ?? null;

        switch ($statusType) {
            case 'Start':
                $this->handleSessionStart($user, $data);
                break;
            case 'Stop':
                $this->handleSessionStop($user, $data);
                break;
            case 'Interim-Update':
                $this->handleInterimUpdate($user, $data);
                break;
            default:
                Log::info("Unhandled accounting status type: {$statusType}", $data);
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleSessionStart(User $user, array $data)
    {
        Log::info("Session start for user: {$user->username}", $data);

        UserSession::create([
            'user_id' => $user->id,
            'username' => $data['User-Name'],
            'router_name' => $data['NAS-Identifier'] ?? 'unknown',
            'ip_address' => $data['Framed-IP-Address'] ?? null,
            'mac_address' => $data['Calling-Station-Id'] ?? null,
            'profile' => $data['Mikrotik-Profile'] ?? null,
            'session_timestamp' => now(),
            'bytes_in' => 0,
            'bytes_out' => 0,
            'used_bytes' => 0,
            'limit_bytes' => $user->data_limit,
        ]);

        $user->update([
            'connection_status' => 'active',
            'current_ip' => $data['Framed-IP-Address'] ?? null,
            'last_online' => now(),
        ]);
    }

    private function handleSessionStop(User $user, array $data)
    {
        Log::info("Session stop for user: {$user->username}", $data);

        $session = UserSession::where('username', $data['User-Name'])
                             ->whereNull('updated_at') // Find active session
                             ->latest()
                             ->first();

        if ($session) {
            $bytesIn = $data['Acct-Input-Octets'] ?? 0;
            $bytesOut = $data['Acct-Output-Octets'] ?? 0;
            $totalBytes = $bytesIn + $bytesOut;

            $session->update([
                'bytes_in' => $bytesIn,
                'bytes_out' => $bytesOut,
                'used_bytes' => $totalBytes,
                'uptime' => $data['Acct-Session-Time'] ?? 0,
            ]);

            // Update user's total data usage
            $user->increment('data_used', $totalBytes);

            // Log data usage for billing
            Log::info("Data usage update", [
                'user' => $user->username,
                'session_bytes' => $totalBytes,
                'total_used' => $user->data_used,
                'limit' => $user->data_limit,
                'remaining' => max(0, ($user->data_limit ?? 0) - $user->data_used)
            ]);

            // If the user has a numeric data limit and has reached or exceeded it, expire their plan
            if ($user->data_limit && $user->data_used >= $user->data_limit) {
                Log::warning("User {$user->username} exhausted data during session stop", [
                    'used' => $user->data_used,
                    'limit' => $user->data_limit,
                ]);

                // Expire due to exhaustion (no rollover)
                try {
                    $subscriptionService = new \App\Services\SubscriptionService();
                    $subscriptionService->expireForExhaustion($user);
                } catch (\Exception $e) {
                    Log::error('Failed to expire exhausted user ' . $user->username . ': ' . $e->getMessage());
                }
            }
        }

        $user->update([
            'connection_status' => 'inactive',
            'last_online' => now(),
        ]);

        // Check if user exceeded data limit and take action
        if ($user->data_used >= $user->data_limit) {
            Log::warning("User {$user->username} exceeded data limit - DISCONNECTING", [
                'used' => $user->data_used,
                'limit' => $user->data_limit,
                'overage' => $user->data_used - $user->data_limit
            ]);

            // Mark user as suspended due to data limit
            $user->update(['connection_status' => 'suspended']);

            // TODO: Send notification email/SMS to user
            // TODO: Schedule automatic sync to RADIUS to block user
        }
    }

    private function handleInterimUpdate(User $user, array $data)
    {
        // Update current session data
        $session = UserSession::where('username', $data['User-Name'])
                             ->whereNull('updated_at')
                             ->latest()
                             ->first();

        if ($session) {
            $newUsed = ($data['Acct-Input-Octets'] ?? 0) + ($data['Acct-Output-Octets'] ?? 0);
            $oldUsed = $session->used_bytes ?? 0;
            $delta = max(0, $newUsed - $oldUsed);

            $session->update([
                'bytes_in' => $data['Acct-Input-Octets'] ?? $session->bytes_in,
                'bytes_out' => $data['Acct-Output-Octets'] ?? $session->bytes_out,
                'used_bytes' => $newUsed,
                'uptime' => $data['Acct-Session-Time'] ?? $session->uptime,
            ]);

            // Increment user's total data usage by the delta since last interim
            if ($delta > 0) {
                $user->increment('data_used', $delta);
                Log::info('Interim update increased user usage', ['username' => $user->username, 'delta' => $delta, 'total_used' => $user->data_used]);

                // If data exhausted now, expire plan immediately
                if ($user->data_limit && $user->data_used >= $user->data_limit) {
                    Log::warning("User {$user->username} exhausted data during interim update", [
                        'used' => $user->data_used,
                        'limit' => $user->data_limit,
                    ]);

                    $user->plan_id = null;
                    $user->plan_expiry = null;
                    $user->connection_status = 'exhausted';
                    $user->save(); // triggers PlanSyncService to move user to default_group

                    try {
                        \App\Models\RadReply::updateOrCreate(
                            ['username' => $user->username, 'attribute' => 'Mikrotik-Total-Limit'],
                            ['op' => ':=', 'value' => '0']
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to set Mikrotik-Total-Limit to 0 for ' . $user->username . ': ' . $e->getMessage());
                    }
                }
            }
        }

        $user->update(['last_online' => now()]);
    }

    private function validateAccountingRequest(Request $request): bool
    {
        // Add proper validation here
        // For now, basic check - you should implement shared secret validation
        // or API key authentication

        $requiredFields = ['User-Name', 'Acct-Status-Type'];
        foreach ($requiredFields as $field) {
            if (!$request->has($field)) {
                return false;
            }
        }

        return true;
    }
}