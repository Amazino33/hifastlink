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

        // Enforce remaining time for session timeout; reject if expired
        $remainingSeconds = $user->plan_expiry ? now()->diffInSeconds($user->plan_expiry, false) : null;
        if (!is_null($remainingSeconds) && $remainingSeconds <= 0) {
            return response()->json(['message' => 'Your plan has expired.'], 422);
        }

        // Self-repair: if a valid subscription exists but user.plan_id is missing, repair it immediately
        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                Log::info('Repaired missing plan_id for user '.$user->id.' using subscription.'.($validSubscription->id ?? ''));
            } catch (\Exception $e) {
                Log::warning('Failed to repair user.plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        // Check for required credentials
        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router credentials missing for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        // Determine login URL
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
     * Attempt a server-side login via the RADIUS bridge.
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
        }

        // Fallback
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
        // General Variables
        $location = $router->nas_identifier ?: $router->name;
        $secret = $router->secret;
        
        $appUrl = rtrim(config('app.url', env('APP_URL', 'https://hifastlink.com')), '/');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: preg_replace('#https?://#', '', $appUrl);
        $dnsName = 'login.wifi';
        $bridgeName = 'bridge';
        $websiteIp = env('WEBSITE_IP', '194.36.184.49');

        // WireGuard Variables
        $wgServerPubKey = env('WG_SERVER_PUBLIC_KEY', 'INSERT_SERVER_PUBLIC_KEY_HERE');
        $wgServerEndpoint = env('WG_SERVER_ENDPOINT', env('RADIUS_PUBLIC_IP', 'INSERT_LINUX_SERVER_PUBLIC_IP_HERE'));
        $wgServerPort = env('WG_SERVER_PORT', '51820');
        $wgListenPort = env('WG_LISTEN_PORT', '13231');
        
        // We use the IP from your Filament form as the internal Router VPN IP
        $wgRouterIp = $router->ip_address ?? '192.168.42.10'; 
        $wgServerIp = env('WG_SERVER_IP', '192.168.42.1');

        // Escape double quotes to prevent syntax breaking
        $escLocation = str_replace('"', '\\"', $location);
        $escSecret = str_replace('"', '\\"', $secret);
        $escDomain = str_replace('"', '\\"', $domain);
        $escDns = str_replace('"', '\\"', $dnsName);
        $escBridge = str_replace('"', '\\"', $bridgeName);
        $escWebsiteIp = str_replace('"', '\\"', $websiteIp);
        $escWgServerPubKey = str_replace('"', '\\"', $wgServerPubKey);
        $escWgServerEndpoint = str_replace('"', '\\"', $wgServerEndpoint);
        $escWgServerPort = str_replace('"', '\\"', $wgServerPort);
        $escWgListenPort = str_replace('"', '\\"', $wgListenPort);
        $escWgRouterIp = str_replace('"', '\\"', $wgRouterIp);
        $escWgServerIp = str_replace('"', '\\"', $wgServerIp);

        // Using Nowdoc (<<<'RSC') so we don't have to escape standard PHP variables 
        // inside the massive router string. It perfectly preserves the user's \$ formatting.
        $template = <<<'RSC'
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT (v6->v7 AUTO-UPGRADE + WIREGUARD)
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
:global WGServerPublicKey  "{WG_SERVER_PUB_KEY}"
:global WGServerEndpoint   "{WG_SERVER_ENDPOINT}"
:global WGServerPort       "{WG_SERVER_PORT}"
:global WGListenPort       "{WG_LISTEN_PORT}"
:global WGRouterIP         "{WG_ROUTER_IP}"
:global WGServerIP         "{WG_SERVER_IP}"

# RADIUS Configuration
:global RadiusSecret "{SECRET}"

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
    
    # Create the post-upgrade setup script
    /system script remove [find name="hifastlink-post-upgrade"]
    /system script add name="hifastlink-post-upgrade" source="
# Post-Upgrade Setup Script - Auto-runs after v7 upgrade
:delay 30s
:put \">> Starting post-upgrade configuration...\"

# Get configuration from global variables
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

# 1. Set Identity
/system/identity set name=\$LocationName
:put \">> Identity set\"

# 2. WireGuard VPN Configuration
:if (\$VPNEnabled) do={
    :put \">> Configuring WireGuard VPN...\"
    
    # Create WireGuard interface
    :do {
        /interface/wireguard remove [find name=\"wg-saas\"]
    } on-error={}
    /interface/wireguard add name=\"wg-saas\" listen-port=\$WGListenPort
    
    # Assign VPN IP to router
    :do {
        /ip/address remove [find interface=\"wg-saas\"]
    } on-error={}
    /ip/address add address=(\$WGRouterIP . \"/24\") interface=\"wg-saas\" network=\"192.168.42.0\"
    
    # Add server as peer
    :do {
        /interface/wireguard/peers remove [find interface=\"wg-saas\"]
    } on-error={}
    /interface/wireguard/peers add interface=\"wg-saas\" public-key=\$WGServerPublicKey endpoint-address=\$WGServerEndpoint endpoint-port=\$WGServerPort allowed-address=(\$WGServerIP . \"/32\") persistent-keepalive=25s
    
    :delay 5s
    :put \">> WireGuard VPN configured\"
}

# 3. Configure RADIUS (via VPN)
:local RadiusIP
:if (\$VPNEnabled) do={
    :set RadiusIP \$WGServerIP
} else={
    :set RadiusIP \"142.93.47.189\"
}

/radius remove [find]
/radius add address=\$RadiusIP secret=\$RadiusSecret service=hotspot timeout=3000ms comment=\"HiFastLink RADIUS\"
:put \">> RADIUS configured\"

# 4. Hotspot Interface
/ip/hotspot set [find] interface=\$BridgeName
:put \">> Hotspot interface set\"

# 5. Hotspot Profile
/ip/hotspot/profile set [find] dns-name=\$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
:put \">> Hotspot profile configured\"

# 6. User Profile
/ip/hotspot/user/profile set [find] shared-users=10
:put \">> User profile configured\"

# 7. Walled Garden - DNS
/ip/hotspot/walled-garden remove [find]
/ip/hotspot/walled-garden add dst-host=(\"*.\" . \$DomainName) comment=\"Allow Dashboard Subdomains\"
/ip/hotspot/walled-garden add dst-host=\$DomainName comment=\"Allow Dashboard Root\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.com\" comment=\"Allow Paystack\"
/ip/hotspot/walled-garden add dst-host=\"*.paystack.co\" comment=\"Allow Paystack Alt\"
/ip/hotspot/walled-garden add dst-host=\"*.sentry.io\" comment=\"Allow Error Logs\"
:put \">> Walled Garden (DNS) configured\"

# 8. Walled Garden - IP
/ip/hotspot/walled-garden/ip remove [find]
/ip/hotspot/walled-garden/ip add action=accept dst-address=\$WebsiteIP comment=\"HiFastLink Server\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=\$WebsiteIP comment=\"HTTPS\"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=\$WebsiteIP comment=\"HTTP\"
/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment=\"DNS\"
:put \">> Walled Garden (IP) configured\"

# 9. DNS
/ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
:put \">> DNS configured\"

# 10. Heartbeat
:local heartbeatURL (\"https://\" . \$DomainName . \"/api/routers/heartbeat?identity=\" . \$LocationName)
/system/scheduler remove [find name=\"heartbeat\"]
/system/scheduler add name=\"heartbeat\" interval=1m on-event=(\"/tool/fetch url=\\\"\" . \$heartbeatURL . \"\\\" mode=https output=none\")
:put \">> Heartbeat configured\"

# 11. Realtime Speed Reporter
/system/scheduler remove [find name=\"realtime-stats\"]
/system/scheduler add name=\"realtime-stats\" interval=10s on-event={
    :local identity [/system/identity get name]
    :local apiURL \"https://hifastlink.com/api/routers/speed\"
    :foreach session in=[/ip/hotspot/active find] do={
        :local user [/ip/hotspot/active get \$session user]
        :local bytesIn [/ip/hotspot/active get \$session bytes-in]
        :local bytesOut [/ip/hotspot/active get \$session bytes-out]
        :local fullURL (\$apiURL . \"?identity=\" . \$identity . \"&user=\" . \$user . \"&bytes_in=\" . \$bytesIn . \"&bytes_out=\" . \$bytesOut)
        :do {
            /tool/fetch url=\$fullURL mode=https output=none
        } on-error={}
    }
}
:put \">> Speed reporter configured\"

# 12. NTP
/system/ntp/client set enabled=yes
:do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
:do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123
:put \">> NTP configured\"

# 13. API
/ip/service set api disabled=no port=8728
:put \">> API enabled\"

:put \"===========================================\"
:put \"   POST-UPGRADE SETUP COMPLETE\"
:put (\"   Router: \" . \$LocationName)
:put (\"   Login: http://\" . \$DNSName)
:if (\$VPNEnabled) do={
    :put \"   VPN: WireGuard Enabled\"
}
:put \"   READY TO USE\"
:put \"===========================================\"

# Clean up
/system/scheduler remove [find name=\"run-post-upgrade\"]
/system/script remove [find name=\"hifastlink-post-upgrade\"]
"
    
    # Create scheduler to run script on startup
    /system scheduler remove [find name="run-post-upgrade"]
    /system scheduler add name="run-post-upgrade" on-event="hifastlink-post-upgrade" start-time=startup interval=0
    
    :put ">> Post-upgrade script created"
    :put ">> Starting upgrade to RouterOS v7..."
    :put ">> Router will reboot in ~2 minutes"
    :put ">> Setup will complete automatically"
    :put ">> Total time: 5-10 minutes"
    
    # Perform upgrade
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
    
    # 1. Set Identity
    /system/identity set name=$LocationName
    :put ">> Identity set"
    
    # 2. WireGuard VPN Configuration
    :if ($VPNEnabled) do={
        :put ">> Configuring WireGuard VPN..."
        
        # Create WireGuard interface
        :do {
            /interface/wireguard remove [find name="wg-saas"]
        } on-error={}
        /interface/wireguard add name="wg-saas" listen-port=$WGListenPort
        
        # Assign VPN IP to router
        :do {
            /ip/address remove [find interface="wg-saas"]
        } on-error={}
        /ip/address add address=($WGRouterIP . "/24") interface="wg-saas" network="192.168.42.0"
        
        # Add server as peer
        :do {
            /interface/wireguard/peers remove [find interface="wg-saas"]
        } on-error={}
        /interface/wireguard/peers add interface="wg-saas" \
            public-key=$WGServerPublicKey \
            endpoint-address=$WGServerEndpoint \
            endpoint-port=$WGServerPort \
            allowed-address=($WGServerIP . "/32") \
            persistent-keepalive=25s
        
        :delay 5s
        :put ">> WireGuard VPN configured"
    }
    
    # 3. Configure RADIUS (via VPN)
    :local RadiusIP
    :if ($VPNEnabled) do={
        :set RadiusIP $WGServerIP
    } else={
        :set RadiusIP "142.93.47.189"
    }
    
    /radius remove [find]
    /radius add address=$RadiusIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
    :put ">> RADIUS configured"
    
    # 4. Hotspot Interface
    /ip/hotspot set [find] interface=$BridgeName
    :put ">> Hotspot interface set"
    
    # 5. Hotspot Profile
    /ip/hotspot/profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
    :put ">> Hotspot profile configured"
    
    # 6. User Profile
    /ip/hotspot/user/profile set [find] shared-users=10
    :put ">> User profile configured"
    
    # 7. Walled Garden - DNS
    /ip/hotspot/walled-garden remove [find]
    /ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard Subdomains"
    /ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
    /ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Allow Paystack"
    /ip/hotspot/walled-garden add dst-host="*.paystack.co" comment="Allow Paystack Alt"
    /ip/hotspot/walled-garden add dst-host="*.sentry.io" comment="Allow Error Logs"
    :put ">> Walled Garden (DNS) configured"
    
    # 8. Walled Garden - IP
    /ip/hotspot/walled-garden/ip remove [find]
    /ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP comment="HiFastLink Server"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=$WebsiteIP comment="HTTP"
    /ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment="DNS"
    :put ">> Walled Garden (IP) configured"
    
    # 9. DNS
    /ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
    :put ">> DNS configured"
    
    # 10. Heartbeat
    :local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
    /system/scheduler remove [find name="heartbeat"]
    /system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"$heartbeatURL\" mode=https output=none")
    :put ">> Heartbeat configured"
    
    # 11. Realtime Speed Reporter
    /system/scheduler remove [find name="realtime-stats"]
    /system/scheduler add name="realtime-stats" interval=10s on-event={
        :local identity [/system/identity get name]
        :local apiURL "https://hifastlink.com/api/routers/speed"
        :foreach session in=[/ip/hotspot/active find] do={
            :local user [/ip/hotspot/active get $session user]
            :local bytesIn [/ip/hotspot/active get $session bytes-in]
            :local bytesOut [/ip/hotspot/active get $session bytes-out]
            :local fullURL ($apiURL . "?identity=" . $identity . "&user=" . $user . "&bytes_in=" . $bytesIn . "&bytes_out=" . $bytesOut)
            :do {
                /tool/fetch url=$fullURL mode=https output=none
            } on-error={}
        }
    }
    :put ">> Speed reporter configured"
    
    # 12. NTP
    /system/ntp/client set enabled=yes
    :do {/system/ntp/client/servers remove [find address=162.159.200.1]} on-error={}
    :do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
    /system/ntp/client/servers add address=162.159.200.1
    /system/ntp/client/servers add address=162.159.200.123
    :put ">> NTP configured"
    
    # 13. API
    /ip/service set api disabled=no port=8728
    :put ">> API enabled"
    
    :put "========================================"
    :put ("   SETUP COMPLETE FOR: " . $LocationName)
    :put ("   Login Link: http://" . $DNSName)
    :put ("   Hotspot Interface: " . $BridgeName)
    :if ($VPNEnabled) do={
        :put "   VPN: WireGuard Enabled"
        :put ("   VPN IP: " . $WGRouterIP)
    }
    :put "   READY TO DEPLOY"
    :put "========================================"
}
RSC;

        $script = str_replace([
            '{LOCATION}', '{DOMAIN}', '{DNSNAME}', '{BRIDGE}', '{WEBSITEIP}',
            '{WG_SERVER_PUB_KEY}', '{WG_SERVER_ENDPOINT}', '{WG_SERVER_PORT}', 
            '{WG_LISTEN_PORT}', '{WG_ROUTER_IP}', '{WG_SERVER_IP}', '{SECRET}'
        ], [
            $escLocation, $escDomain, $escDns, $escBridge, $escWebsiteIp,
            $escWgServerPubKey, $escWgServerEndpoint, $escWgServerPort, 
            $escWgListenPort, $escWgRouterIp, $escWgServerIp, $escSecret
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