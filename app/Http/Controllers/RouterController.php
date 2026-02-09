<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Router;

class RouterController extends Controller
{

    /**
     * Return router login credentials for the authenticated user.
     */
    public function credentials(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Determine if user has an active subscription.
        // Prefer direct subscription table query if Subscription model exists; otherwise fall back to user plan fields.
        $validSubscription = null;

        if (class_exists(\App\Models\Subscription::class)) {
            $validSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->where(function ($q) {
                    $q->where('data_remaining', '>', 0)
                      ->orWhereNull('data_limit');
                })
                ->orderBy('expires_at', 'desc')
                ->first();
        } else {
            // fallback: check user's plan_expiry and remaining bytes
            $hasExpiry = $user->plan_expiry && $user->plan_expiry->isFuture();
            $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));

            if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
                $validSubscription = (object) [
                    'plan_id' => $user->plan_id,
                    'expires_at' => $user->plan_expiry,
                ];
            }
        }

        if (! $validSubscription) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        // Self-repair: if a valid subscription exists but user.plan_id is missing, repair it immediately
        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                \Illuminate\Support\Facades\Log::info('Repaired missing plan_id for user '.$user->id.' using subscription.'.($validSubscription->id ?? ''));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to repair user.plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        // Check for required credentials
        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router credentials missing for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        // Determine login URL (use services.mikrotik.gateway or env MIKROTIK_GATEWAY, fallback to login.wifi)
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';

        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (!preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        return response()->json([
            'username' => $user->username,
            'password' => $user->radius_password,
            'login_url' => $loginUrl,
            'dashboard_url' => route('dashboard'),
        ]);
    }

    /**
     * Attempt a server-side login via the RADIUS bridge. Intended for captive portal flows.
     * Expects optional 'mac', 'ip', 'link-login' in the request.
     */
    public function bridgeLogin(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!method_exists($user, 'isSubscriptionActive') || !$user->isSubscriptionActive()) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router bridge login failed - missing credentials for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        $mac = $request->input('mac');
        $ip = $request->input('ip');
        $linkLogin = $request->input('link-login') ?? $request->input('link-login-only') ?? $request->input('link-orig');

        $bridgeUrl = rtrim(config('services.radius.bridge_url') ?? env('RADIUS_BRIDGE_URL', ''), '/');
        $secret = config('services.radius.secret_key') ?? env('RADIUS_SECRET_KEY', null);

        // If we have a bridge, attempt server-side POST to router via bridge
        if ($bridgeUrl && $secret) {
            try {
                $resp = \Illuminate\Support\Facades\Http::post($bridgeUrl . '/login', array_filter([
                    'username' => $user->username,
                    'password' => $user->radius_password,
                    'secret' => $secret,
                    'mac' => $mac,
                    'ip' => $ip,
                    'link' => $linkLogin,
                ]));

                if ($resp->successful()) {
                    Log::info('Router bridge login successful for user id: ' . $user->id);
                    return response()->json(['success' => true, 'redirect' => $linkLogin ?? null]);
                }

                Log::warning('Router bridge login returned non-200 for user id ' . $user->id, ['body' => $resp->body()]);
            } catch (\Exception $e) {
                Log::error('Router bridge login error for user id ' . $user->id . ': ' . $e->getMessage());
            }

            // If bridge failed, fallthrough to client-side submission fallback
        }

        // Fallback: return login info for client to submit directly (includes dashboard URL so router can return the user)
        $fallbackGateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';
        $fallbackLoginUrl = (strpos($fallbackGateway, '://') === false ? 'http://' . $fallbackGateway : $fallbackGateway);
        if (!preg_match('#/login#', $fallbackLoginUrl)) {
            $fallbackLoginUrl = rtrim($fallbackLoginUrl, '/') . '/login';
        }

        return response()->json([
            'success' => false,
            'message' => 'Bridge unavailable. You will be redirected to router to complete login.',
            'username' => $user->username,
            'password' => $user->radius_password,
            'login_url' => $fallbackLoginUrl,
            'dashboard_url' => route('dashboard'),
            'redirect' => $linkLogin ?? null,
        ]);
    }

    /**
     * Download generated MikroTik configuration (.rsc) for a router.
     */
    public function downloadConfig(Router $router)
    {
        // Values to inject
        $location = $router->nas_identifier ?: $router->name;
        $serverIp = env('RADIUS_PUBLIC_IP', env('RADIUS_DB_HOST', config('database.connections.radius.host', '142.93.47.189')));
        $secret = $router->secret;

        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://example.com')), '/');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: preg_replace('#https?://#', '', $appUrl);
        $dnsName = 'login.wifi';
        $bridgeName = 'bridge';

        // Escape double quotes in values
        $escLocation = str_replace('"', '\\"', $location);
        $escServerIp = str_replace('"', '\\"', $serverIp);
        $escSecret = str_replace('"', '\\"', $secret);
        $escDomain = str_replace('"', '\\"', $domain);
        $escDns = str_replace('"', '\\"', $dnsName);
        $escBridge = str_replace('"', '\\"', $bridgeName);

        $template = <<<'RSC'
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT (v6 & v7 COMPATIBLE)
#  Author: Gem (The Developer)
# ==================================================

# --- 1. CONFIGURATION VARIABLES (EDIT HERE) ---
:global LocationName "{LOCATION}"
:global ServerIP     "{SERVERIP}"
:global RadiusSecret "{SECRET}"
:global DomainName   "{DOMAIN}"
:global DNSName      "{DNSNAME}"
:global BridgeName   "{BRIDGE}"

# --- 2. DETECT ROUTEROS VERSION ---
:global rosVersion [/system resource get version]
:global isV7 false
:if ([:pick $rosVersion 0 1] = "7") do={
    :set isV7 true
    :put ">> Detected RouterOS v7"
} else={
    :put ">> Detected RouterOS v6"
}

# --------------------------------------------------
#       DO NOT EDIT BELOW THIS LINE
# --------------------------------------------------

:put (">> Starting Setup for " . $LocationName . "...")

# 1. Set Identity
:if ($isV7) do={
    /system/identity set name=$LocationName
} else={
    /system identity set name=$LocationName
}

# 2. Configure RADIUS Client
/radius remove [find]
/radius add address=$ServerIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
:put ">> RADIUS Configured"

# 3. Update Hotspot Server Interface to Bridge
:if ($isV7) do={
    /ip/hotspot set [find] interface=$BridgeName
} else={
    /ip hotspot set [find] interface=$BridgeName
}
:put (">> Hotspot Server Interface set to: " . $BridgeName)

# 4. Configure Hotspot Server Profile
:if ($isV7) do={
    /ip/hotspot/profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-chap,http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
} else={
    /ip hotspot profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-chap,http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
}
:put (">> Hotspot DNS Name set to: " . $DNSName . " (Applied to ALL profiles)")

# 5. Configure User Profile (Limits)
:if ($isV7) do={
    /ip/hotspot/user/profile set [find] shared-users=10
} else={
    /ip hotspot user profile set [find] shared-users=10
}
:put ">> User Profile Updated (10 Devices Allowed)"

# 6. Walled Garden (Allow Dashboard & Payments)
:if ($isV7) do={
    /ip/hotspot/walled-garden remove [find]
    /ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard"
    /ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
    /ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Allow Paystack"
    /ip/hotspot/walled-garden add dst-host="*.paystack.co" comment="Allow Paystack"
    /ip/hotspot/walled-garden add dst-host="*.sentry.io" comment="Allow Error Logs"
} else={
    /ip hotspot walled-garden remove [find]
    /ip hotspot walled-garden add dst-host=("*" . $DomainName) comment="Allow Dashboard"
    /ip hotspot walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
    /ip hotspot walled-garden add dst-host=*paystack.com comment="Allow Paystack"
    /ip hotspot walled-garden add dst-host=*paystack.co comment="Allow Paystack"
    /ip hotspot walled-garden add dst-host=*sentry.io comment="Allow Error Logs"
}
:put ">> Walled Garden Configured"

# 7. NTP Client (Time Sync)
:if ($isV7) do={
    /system/ntp/client set enabled=yes
    :do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
    :do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
    /system/ntp/client/servers add address=162.159.200.1
    /system/ntp/client/servers add address=162.159.200.123
} else={
    /system ntp client set enabled=yes primary-ntp=162.159.200.1 secondary-ntp=162.159.200.123
}
:put ">> Time Sync Enabled"

# 8. Enable API
:if ($isV7) do={
    /ip/service set api disabled=no port=8728
} else={
    /ip service set api disabled=no port=8728
}
:put ">> API Service Enabled"

:put "========================================"
:put ("   SETUP COMPLETE FOR: " . $LocationName)
:put ("   Login Link: http://" . $DNSName)
:put ("   Hotspot Interface: " . $BridgeName)
:put "   READY TO DEPLOY"
:put "========================================"
RSC;

        $script = str_replace([
            '{LOCATION}', '{SERVERIP}', '{SECRET}', '{DOMAIN}', '{DNSNAME}', '{BRIDGE}'
        ], [
            $escLocation, $escServerIp, $escSecret, $escDomain, $escDns, $escBridge
        ], $template);

        $filename = 'router-' . ($router->nas_identifier ?: $router->id) . '.rsc';

        return response()->streamDownload(function() use ($script) {
            echo $script;
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
