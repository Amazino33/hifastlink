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
            $hasExpiry = $user->plan_expiry && $user->plan_expiry->isFuture();
            $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));

            if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
                $validSubscription = (object) [
                    'plan_id' => $user->plan_id,
                    'expires_at' => $user->plan_expiry,
                ];
            }
        }

        if (!$validSubscription) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        $remainingSeconds = $user->plan_expiry ? now()->diffInSeconds($user->plan_expiry, false) : null;
        if (!is_null($remainingSeconds) && $remainingSeconds <= 0) {
            return response()->json(['message' => 'Your plan has expired.'], 422);
        }

        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                Log::info('Repaired missing plan_id for user '.$user->id);
            } catch (\Exception $e) {
                Log::warning('Failed to repair user.plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router credentials missing for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        $gateway = config('services.mikrotik.gateway', 'login.wifi');
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
     * Bridge login
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
        }

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
     * Download optimized MikroTik configuration (.rsc) for a router with WireGuard + Auto-upgrade
     */
    public function downloadConfig(Router $router)
    {
        // 1. Gather all variables
        $location = $router->nas_identifier ?: $router->name;
        $secret = $router->secret;
        $routerVpnIp = $router->ip_address ?? '192.168.42.10'; 

        $wgServerPublicKey = config('services.wireguard.public_key', env('WG_SERVER_PUBLIC_KEY', 'INSERT_KEY'));
        $wgServerEndpoint = config('services.wireguard.endpoint', env('WG_SERVER_ENDPOINT', '194.36.184.49'));
        $wgServerPort = config('services.wireguard.port', env('WG_SERVER_PORT', '51820'));
        $wgListenPort = config('services.wireguard.listen_port', env('WG_LISTEN_PORT', '13231'));
        $wgServerIp = config('services.wireguard.server_ip', env('WG_SERVER_IP', '192.168.42.1'));

        $domain = config('services.mikrotik.domain', parse_url(config('app.url', 'https://hifastlink.com'), PHP_URL_HOST));
        $dnsName = config('services.mikrotik.dns_name', 'login.wifi');
        $bridgeName = 'bridge';
        $websiteIp = config('services.mikrotik.website_ip', env('WEBSITE_IP', '194.36.184.49'));
        
        $radiusIp = $router->vpn_enabled ? $wgServerIp : '142.93.47.189';

        // 2. Build WireGuard block conditionally in PHP (no RouterOS logic needed)
        $wgCommands = "";
        if ($router->vpn_enabled) {
            $wgCommands = "
:do { /interface/wireguard remove [find name=\"wg-saas\"] } on-error={}
/interface/wireguard add name=\"wg-saas\" listen-port={$wgListenPort}
:do { /ip/address remove [find interface=\"wg-saas\"] } on-error={}
/ip/address add address=\"{$routerVpnIp}/24\" interface=\"wg-saas\" network=\"192.168.42.0\"
:do { /interface/wireguard/peers remove [find interface=\"wg-saas\"] } on-error={}
/interface/wireguard/peers add interface=\"wg-saas\" public-key=\"{$wgServerPublicKey}\" endpoint-address=\"{$wgServerEndpoint}\" endpoint-port={$wgServerPort} allowed-address=\"{$wgServerIp}/32\" persistent-keepalive=25s
:delay 5s
";
        }

        // 3. Build the flat, base RouterOS configuration
        $baseCommands = "
/system/identity set name=\"{$location}\"
{$wgCommands}
/radius remove [find]
/radius add address=\"{$radiusIp}\" secret=\"{$secret}\" service=hotspot timeout=3000ms comment=\"HiFastLink RADIUS\"
/ip/hotspot set [find] interface=\"{$bridgeName}\"
/ip/hotspot/profile set [find] dns-name=\"{$dnsName}\" html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
/ip/hotspot/user/profile set [find] shared-users=10
/ip/hotspot/walled-garden remove [find]
/ip/hotspot/walled-garden add dst-host=\"*.{$domain}\" comment=\"Allow Dashboard\"
/ip/hotspot/walled-garden add dst-host=\"{$domain}\" comment=\"Allow Root\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.com\" comment=\"Paystack\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.co\" comment=\"Paystack\"
/ip/hotspot/walled-garden add dst-host=\"*.sentry.io\" comment=\"Logs\"
/ip/hotspot/walled-garden/ip remove [find]
/ip/hotspot/walled-garden/ip add action=accept dst-address=\"{$websiteIp}\" comment=\"Server\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=\"{$websiteIp}\" comment=\"HTTPS\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=\"{$websiteIp}\" comment=\"HTTP\"
/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment=\"DNS\"
/ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
/system/scheduler remove [find name=\"heartbeat\"] on-error={}
/system/scheduler add name=\"heartbeat\" interval=1m on-event=\"/tool/fetch url=\\\"https://{$domain}/api/routers/heartbeat?identity={$location}\\\" mode=https output=none\"
/system/scheduler remove [find name=\"realtime-stats\"] on-error={}
/system/scheduler add name=\"realtime-stats\" interval=10s on-event=\":local apiURL \\\"https://{$domain}/api/routers/speed\\\"; :foreach session in=[/ip/hotspot/active find] do={:local user [/ip/hotspot/active get \\\$session user]; :local bytesIn [/ip/hotspot/active get \\\$session bytes-in]; :local bytesOut [/ip/hotspot/active get \\\$session bytes-out]; :do {/tool/fetch url=(\\\$apiURL . \\\"?identity={$location}&user=\\\" . \\\$user . \\\"&bytes_in=\\\" . \\\$bytesIn . \\\"&bytes_out=\\\" . \\\$bytesOut) mode=https output=none} on-error={}}\"
/system/ntp/client set enabled=yes
:do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
:do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123
/ip/service set api disabled=no port=8728
:put \">> SETUP COMPLETE FOR {$location}\"
";

        // 4. Safely escape the base commands so they can be injected into the v6 'source' parameter
        $escapedForV6 = addcslashes($baseCommands, '"$\\');

        // 5. Build the final executable script
        $script = <<<RSC
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT
#  IMPORTANT: Do not paste this into the terminal!
#  Open Winbox -> Files -> Upload this file.
#  Then run: /import file-name=router-{$location}.rsc
# ==================================================

:local currentVersion [:pick [/system resource get version] 0 1]

:if (\$currentVersion = "6") do={
    :put ">> DETECTED: RouterOS v6"
    :put ">> ACTION: Scheduling v7 Upgrade and setup..."
    
    :do { /system script remove [find name="hifastlink-post-upgrade"] } on-error={}
    /system script add name="hifastlink-post-upgrade" source=":delay 30s; {$escapedForV6} /system/scheduler remove [find name=\"run-post-upgrade\"] on-error={}; /system/script remove [find name=\"hifastlink-post-upgrade\"] on-error={};"
    
    :do { /system scheduler remove [find name="run-post-upgrade"] } on-error={}
    /system scheduler add name="run-post-upgrade" on-event="hifastlink-post-upgrade" start-time=startup interval=0
    
    :put ">> Router will reboot in ~2 minutes for upgrade."
    /system package update set channel=stable
    /system package update check-for-updates
    :delay 15s
    /system package update download
    :delay 60s
    /system package update install
} else={
    :put ">> DETECTED: RouterOS v7 - Running setup directly..."
    {$baseCommands}
}
RSC;

        $filename = 'router-' . ($router->nas_identifier ?: $router->id) . '.rsc';

        return response()->streamDownload(function() use ($script) {
            echo $script;
        }, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}