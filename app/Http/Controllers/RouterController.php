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

        // [Keep your existing subscription validation logic here]
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
     * Bridge login [Keep your existing implementation]
     */
    public function bridgeLogin(Request $request)
    {
        // Keep your existing bridge login logic
    }

    /**
     * Download generated MikroTik configuration (.rsc) for a router with WireGuard + Auto-upgrade
     */
    public function downloadConfig(Router $router)
    {
        // Router-specific values
        $location = $router->nas_identifier ?: $router->name;
        $secret = $router->secret;
        $routerVpnIp = $router->vpn_ip;

        // Global WireGuard settings from config
        $wgServerPublicKey = config('services.wireguard.server_public_key');
        $wgServerEndpoint = config('services.wireguard.server_endpoint');
        $wgServerPort = config('services.wireguard.server_port', '51820');
        $wgListenPort = config('services.wireguard.listen_port', '13231');
        $wgServerIp = config('services.wireguard.server_ip', '192.168.42.1');

        // Global Mikrotik settings
        $domain = config('services.mikrotik.domain', parse_url(config('app.url'), PHP_URL_HOST));
        $dnsName = config('services.mikrotik.dns_name', 'login.wifi');
        $bridgeName = 'bridge';
        $websiteIp = config('services.mikrotik.website_ip', '194.36.184.49');

        // Escape for script injection
        $esc = function($val) {
            return str_replace('"', '\\"', $val);
        };

        $template = <<<'RSC'
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT (v6→v7 AUTO-UPGRADE + WIREGUARD)
#  Author: Gem (The Developer)
# ==================================================

# --- 1. CONFIGURATION VARIABLES (EDIT HERE) ---
:global LocationName "{LOCATION}"
:global DomainName   "{DOMAIN}"
:global DNSName      "{DNSNAME}"
:global BridgeName   "{BRIDGE}"
:global WebsiteIP    "{WEBSITEIP}"

# WireGuard VPN Configuration
:global VPNEnabled         true
:global WGServerPublicKey  "{WG_SERVER_PUBLIC_KEY}"
:global WGServerEndpoint   "{WG_SERVER_ENDPOINT}"
:global WGServerPort       "{WG_SERVER_PORT}"
:global WGListenPort       "{WG_LISTEN_PORT}"
:global WGRouterIP         "{WG_ROUTER_IP}"
:global WGServerIP         "{WG_SERVER_IP}"

# RADIUS Configuration
:global RadiusSecret "{RADIUS_SECRET}"

# --- 2. DETECT ROUTEROS VERSION ---
:local currentVersion [:pick [/system resource get version] 0 1]

# ==================================================
#  IF v6: CREATE POST-UPGRADE SCRIPT AND UPGRADE
# ==================================================

:if ($currentVersion = "6") do={
    :put "=========================================="
    :put "   DETECTED: RouterOS v6"
    :put "   ACTION: Upgrading to v7"
    :put "=========================================="
    
    /system script remove [find name="hifastlink-post-upgrade"]
    /system script add name="hifastlink-post-upgrade" source="
:delay 30s
:put \">> Starting post-upgrade configuration...\"

:global LocationName
:global DomainName
:global DNSName
:global BridgeName
:global WebsiteIP
:global VPNEnabled
:global WGServerPublicKey
:global WGServerEndpoint
:global WGServerPort
:global WGListenPort
:global WGRouterIP
:global WGServerIP
:global RadiusSecret

:put (\">> Configuring: \" . \$LocationName)

/system/identity set name=\$LocationName
:put \">> Identity set\"

:if (\$VPNEnabled) do={
    :put \">> Configuring WireGuard VPN...\"
    :do {/interface/wireguard remove [find name=\"wg-saas\"]} on-error={}
    /interface/wireguard add name=\"wg-saas\" listen-port=\$WGListenPort
    :do {/ip/address remove [find interface=\"wg-saas\"]} on-error={}
    /ip/address add address=(\$WGRouterIP . \"/24\") interface=\"wg-saas\" network=\"192.168.42.0\"
    :do {/interface/wireguard/peers remove [find interface=\"wg-saas\"]} on-error={}
    /interface/wireguard/peers add interface=\"wg-saas\" public-key=\$WGServerPublicKey endpoint-address=\$WGServerEndpoint endpoint-port=\$WGServerPort allowed-address=(\$WGServerIP . \"/32\") persistent-keepalive=25s
    :delay 5s
    :put \">> WireGuard configured\"
}

:local RadiusIP
:if (\$VPNEnabled) do={:set RadiusIP \$WGServerIP} else={:set RadiusIP \"142.93.47.189\"}
/radius remove [find]
/radius add address=\$RadiusIP secret=\$RadiusSecret service=hotspot timeout=3000ms comment=\"HiFastLink RADIUS\"
:put \">> RADIUS configured\"

/ip/hotspot set [find] interface=\$BridgeName
:put \">> Hotspot interface set\"

/ip/hotspot/profile set [find] dns-name=\$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
:put \">> Hotspot profile configured\"

/ip/hotspot/user/profile set [find] shared-users=10
:put \">> User profile configured\"

/ip/hotspot/walled-garden remove [find]
/ip/hotspot/walled-garden add dst-host=(\"*.\" . \$DomainName) comment=\"Allow Dashboard\"
/ip/hotspot/walled-garden add dst-host=\$DomainName comment=\"Allow Root\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.com\" comment=\"Paystack\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.co\" comment=\"Paystack\"
/ip/hotspot/walled-garden add dst-host=\"*.sentry.io\" comment=\"Logs\"
:put \">> Walled Garden (DNS) configured\"

/ip/hotspot/walled-garden/ip remove [find]
/ip/hotspot/walled-garden/ip add action=accept dst-address=\$WebsiteIP comment=\"Server\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=\$WebsiteIP comment=\"HTTPS\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=\$WebsiteIP comment=\"HTTP\"
/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment=\"DNS\"
:put \">> Walled Garden (IP) configured\"

/ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
:put \">> DNS configured\"

:local heartbeatURL (\"https://\" . \$DomainName . \"/api/routers/heartbeat?identity=\" . \$LocationName)
/system/scheduler remove [find name=\"heartbeat\"]
/system/scheduler add name=\"heartbeat\" interval=1m on-event=(\"/tool/fetch url=\\\"\" . \$heartbeatURL . \"\\\" mode=https output=none\")
:put \">> Heartbeat configured\"

/system/scheduler remove [find name=\"realtime-stats\"]
/system/scheduler add name=\"realtime-stats\" interval=10s on-event={
    :local identity [/system/identity get name]
    :local apiURL \"https://{DOMAIN}/api/routers/speed\"
    :foreach session in=[/ip/hotspot/active find] do={
        :local user [/ip/hotspot/active get \$session user]
        :local bytesIn [/ip/hotspot/active get \$session bytes-in]
        :local bytesOut [/ip/hotspot/active get \$session bytes-out]
        :local fullURL (\$apiURL . \"?identity=\" . \$identity . \"&user=\" . \$user . \"&bytes_in=\" . \$bytesIn . \"&bytes_out=\" . \$bytesOut)
        :do {/tool/fetch url=\$fullURL mode=https output=none} on-error={}
    }
}
:put \">> Speed reporter configured\"

/system/ntp/client set enabled=yes
:do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
:do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123
:put \">> NTP configured\"

/ip/service set api disabled=no port=8728
:put \">> API enabled\"

:put \"===========================================\"
:put \"   SETUP COMPLETE\"
:put (\"   Router: \" . \$LocationName)
:put (\"   Login: http://\" . \$DNSName)
:put \"   VPN: WireGuard Enabled\"
:put \"   READY\"
:put \"===========================================\"

/system/scheduler remove [find name=\"run-post-upgrade\"]
/system/script remove [find name=\"hifastlink-post-upgrade\"]
"
    
    /system scheduler remove [find name="run-post-upgrade"]
    /system scheduler add name="run-post-upgrade" on-event="hifastlink-post-upgrade" start-time=startup interval=0
    
    :put ">> Upgrading to RouterOS v7..."
    :put ">> Router will reboot in ~2 minutes"
    :put ">> Total time: 5-10 minutes"
    
    /system package update set channel=stable
    /system package update check-for-updates
    :delay 15s
    /system package update download
    :delay 60s
    /system package update install
}

# ==================================================
#  IF v7: RUN SETUP DIRECTLY
# ==================================================

:if ($currentVersion = "7") do={
    :put "=========================================="
    :put "   DETECTED: RouterOS v7"
    :put "   ACTION: Running setup"
    :put "=========================================="
    
    :put (">> Starting Setup for " . $LocationName . "...")
    
    /system/identity set name=$LocationName
    :put ">> Identity set"
    
    :if ($VPNEnabled) do={
        :put ">> Configuring WireGuard VPN..."
        :do {/interface/wireguard remove [find name="wg-saas"]} on-error={}
        /interface/wireguard add name="wg-saas" listen-port=$WGListenPort
        :do {/ip/address remove [find interface="wg-saas"]} on-error={}
        /ip/address add address=($WGRouterIP . "/24") interface="wg-saas" network="192.168.42.0"
        :do {/interface/wireguard/peers remove [find interface="wg-saas"]} on-error={}
        /interface/wireguard/peers add interface="wg-saas" public-key=$WGServerPublicKey endpoint-address=$WGServerEndpoint endpoint-port=$WGServerPort allowed-address=($WGServerIP . "/32") persistent-keepalive=25s
        :delay 5s
        :put ">> WireGuard configured"
    }
    
    :local RadiusIP
    :if ($VPNEnabled) do={:set RadiusIP $WGServerIP} else={:set RadiusIP "142.93.47.189"}
    /radius remove [find]
    /radius add address=$RadiusIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
    :put ">> RADIUS configured"
    
    /ip/hotspot set [find] interface=$BridgeName
    :put ">> Hotspot interface set"
    
    /ip/hotspot/profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
    :put ">> Hotspot profile configured"
    
    /ip/hotspot/user/profile set [find] shared-users=10
    :put ">> User profile configured"
    
    /ip/hotspot/walled-garden remove [find]
    /ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard"
    /ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Root"
    /ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Paystack"
    /ip/hotspot/walled-garden add dst-host="*.paystack.co" comment="Paystack"
    /ip/hotspot/walled-garden add dst-host="*.sentry.io" comment="Logs"
    :put ">> Walled Garden (DNS) configured"
    
    /ip/hotspot/walled-garden/ip remove [find]
    /ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP comment="Server"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=$WebsiteIP comment="HTTP"
    /ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment="DNS"
    :put ">> Walled Garden (IP) configured"
    
    /ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
    :put ">> DNS configured"
    
    :local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
    /system/scheduler remove [find name="heartbeat"]
    /system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"$heartbeatURL\" mode=https output=none")
    :put ">> Heartbeat configured"
    
    /system/scheduler remove [find name="realtime-stats"]
    /system/scheduler add name="realtime-stats" interval=10s on-event={
        :local identity [/system/identity get name]
        :local apiURL "https://{DOMAIN}/api/routers/speed"
        :foreach session in=[/ip/hotspot/active find] do={
            :local user [/ip/hotspot/active get $session user]
            :local bytesIn [/ip/hotspot/active get $session bytes-in]
            :local bytesOut [/ip/hotspot/active get $session bytes-out]
            :local fullURL ($apiURL . "?identity=" . $identity . "&user=" . $user . "&bytes_in=" . $bytesIn . "&bytes_out=" . $bytesOut)
            :do {/tool/fetch url=$fullURL mode=https output=none} on-error={}
        }
    }
    :put ">> Speed reporter configured"
    
    /system/ntp/client set enabled=yes
    :do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
    :do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
    /system/ntp/client/servers add address=162.159.200.1
    /system/ntp/client/servers add address=162.159.200.123
    :put ">> NTP configured"
    
    /ip/service set api disabled=no port=8728
    :put ">> API enabled"
    
    :put "========================================"
    :put ("   SETUP COMPLETE FOR: " . $LocationName)
    :put ("   Login Link: http://" . $DNSName)
    :put ("   VPN IP: " . $WGRouterIP)
    :put "   VPN: WireGuard Enabled"
    :put "   READY TO DEPLOY"
    :put "========================================"
}
RSC;

        $script = str_replace([
            '{LOCATION}',
            '{DOMAIN}',
            '{DNSNAME}',
            '{BRIDGE}',
            '{WEBSITEIP}',
            '{WG_SERVER_PUBLIC_KEY}',
            '{WG_SERVER_ENDPOINT}',
            '{WG_SERVER_PORT}',
            '{WG_LISTEN_PORT}',
            '{WG_ROUTER_IP}',
            '{WG_SERVER_IP}',
            '{RADIUS_SECRET}',
        ], [
            $esc($location),
            $esc($domain),
            $esc($dnsName),
            $esc($bridgeName),
            $esc($websiteIp),
            $esc($wgServerPublicKey),
            $esc($wgServerEndpoint),
            $esc($wgServerPort),
            $esc($wgListenPort),
            $esc($routerVpnIp),
            $esc($wgServerIp),
            $esc($secret),
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