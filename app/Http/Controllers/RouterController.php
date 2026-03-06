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
        $wgServerPubKey  = env('WG_SERVER_PUBLIC_KEY', 'INSERT_SERVER_PUBLIC_KEY_HERE');
        $wgServerEndpoint = env('WG_SERVER_ENDPOINT', env('RADIUS_PUBLIC_IP', 'INSERT_LINUX_SERVER_PUBLIC_IP_HERE'));
        $wgServerPort    = env('WG_SERVER_PORT', '51820');
        $wgListenPort    = env('WG_LISTEN_PORT', '13231');
        $wgRouterIp      = $router->vpn_ip ?? '192.168.42.10';
        $wgServerIp      = env('WG_SERVER_IP', '192.168.42.1');
        $wgRouterPrivKey = $router->wireguard_private_key ?? '';

        // Hotspot Variables
        $wifiSsid     = $router->wifi_ssid ?? 'HiFastLink';
        $wifiPassword = $router->wifi_password ?? '';

        // Escape double quotes
        $escLocation         = str_replace('"', '\\"', $location);
        $escSecret           = str_replace('"', '\\"', $secret);
        $escDomain           = str_replace('"', '\\"', $domain);
        $escDns              = str_replace('"', '\\"', $dnsName);
        $escBridge           = str_replace('"', '\\"', $bridgeName);
        $escWebsiteIp        = str_replace('"', '\\"', $websiteIp);
        $escWgServerPubKey   = str_replace('"', '\\"', $wgServerPubKey);
        $escWgServerEndpoint = str_replace('"', '\\"', $wgServerEndpoint);
        $escWgServerPort     = str_replace('"', '\\"', $wgServerPort);
        $escWgListenPort     = str_replace('"', '\\"', $wgListenPort);
        $escWgRouterIp       = str_replace('"', '\\"', $wgRouterIp);
        $escWgServerIp       = str_replace('"', '\\"', $wgServerIp);
        $escWgRouterPrivKey  = str_replace('"', '\\"', $wgRouterPrivKey);
        $escWifiSsid         = str_replace('"', '\\"', $wifiSsid);
        $escWifiPassword     = str_replace('"', '\\"', $wifiPassword);

        $template = <<<'RSC'
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT (RouterOS v7 ONLY)
#  Author: Gem (The Developer)
#  Note: Fully idempotent - safe to run on any router
#        without a factory reset.
# ==================================================

# --- CONFIGURATION VARIABLES ---
:global LocationName "{LOCATION}"
:global DomainName   "{DOMAIN}"
:global DNSName      "{DNSNAME}"
:global BridgeName   "{BRIDGE}"
:global WebsiteIP    "{WEBSITEIP}"

# WireGuard VPN
:global VPNEnabled         true
:global WGServerPublicKey  "{WG_SERVER_PUB_KEY}"
:global WGRouterPrivateKey "{WG_ROUTER_PRIV_KEY}"
:global WGServerEndpoint   "{WG_SERVER_ENDPOINT}"
:global WGServerPort       "{WG_SERVER_PORT}"
:global WGListenPort       "{WG_LISTEN_PORT}"
:global WGRouterIP         "{WG_ROUTER_IP}"
:global WGServerIP         "{WG_SERVER_IP}"

# WiFi
:global WifiSSID "{WIFI_SSID}"
:global WifiPass "{WIFI_PASS}"

# RADIUS
:global RadiusSecret "{SECRET}"

# ==================================================

:put "=========================================="
:put "   HIFASTLINK SETUP - RouterOS v7"
:put ("   Configuring: " . $LocationName)
:put "=========================================="

# --------------------------------------------------
# 0. BRIDGE SETUP
# --------------------------------------------------

# Create bridge if missing
:if ([:len [/interface/bridge find name=$BridgeName]] = 0) do={
    /interface/bridge add name=$BridgeName protocol-mode=rstp
    :put ">> Bridge created"
} else={
    :put ">> Bridge already exists"
}

# Add ether2-5 to bridge, evicting from any other bridge first
:foreach port in={"ether2";"ether3";"ether4";"ether5"} do={
    :if ([:len [/interface find name=$port]] > 0) do={
        :foreach bp in=[/interface/bridge/port find interface=$port] do={
            :if ([/interface/bridge/port get $bp bridge] != $BridgeName) do={
                /interface/bridge/port remove $bp
            }
        }
        :if ([:len [/interface/bridge/port find interface=$port bridge=$BridgeName]] = 0) do={
            /interface/bridge/port add interface=$port bridge=$BridgeName
        }
    }
}

# Detect WiFi interface and add to bridge, evicting from other bridges first
:local wifiIface ""
:if ([:len [/interface find name="wifi1"]] > 0) do={ :set wifiIface "wifi1" }
:if ([:len [/interface find name="wlan1"]] > 0) do={ :set wifiIface "wlan1" }
:if ($wifiIface != "") do={
    :foreach bp in=[/interface/bridge/port find interface=$wifiIface] do={
        :if ([/interface/bridge/port get $bp bridge] != $BridgeName) do={
            /interface/bridge/port remove $bp
        }
    }
    :if ([:len [/interface/bridge/port find interface=$wifiIface bridge=$BridgeName]] = 0) do={
        /interface/bridge/port add interface=$wifiIface bridge=$BridgeName
    }
    :put (">> WiFi " . $wifiIface . " added to bridge")
}

# Set bridge IP if not set
:if ([:len [/ip/address find interface=$BridgeName]] = 0) do={
    /ip/address add address="192.168.88.1/24" interface=$BridgeName
    :put ">> Bridge IP set to 192.168.88.1"
}

# Create IP pool if missing
:if ([:len [/ip/pool find name="hs-pool"]] = 0) do={
    /ip/pool add name="hs-pool" ranges="192.168.88.10-192.168.88.254"
    :put ">> IP pool created"
} else={
    :put ">> IP pool already exists"
}

# DHCP server on bridge
:if ([:len [/ip/dhcp-server find interface=$BridgeName]] = 0) do={
    /ip/dhcp-server add name="defconf" interface=$BridgeName address-pool="hs-pool" disabled=no
    :do { /ip/dhcp-server/network remove [find address="192.168.88.0/24"] } on-error={}
    /ip/dhcp-server/network add address="192.168.88.0/24" gateway="192.168.88.1" dns-server="192.168.88.1"
    :put ">> DHCP server created"
} else={
    /ip/dhcp-server set [find interface=$BridgeName] address-pool=hs-pool disabled=no
    :put ">> DHCP server pool updated to hs-pool"
}
:put ">> Bridge setup complete"

# --------------------------------------------------
# 0b. WIFI INTERFACE
# --------------------------------------------------

:local wifiList [/interface/wifi find]
:if ([:len $wifiList] > 0) do={
    :set wifiIface [/interface/wifi get ($wifiList->0) name]
    :if ($WifiPass != "") do={
        :if ([:len [/interface/wifi/security find name="hifastlink-sec"]] = 0) do={
            /interface/wifi/security add name="hifastlink-sec" authentication-types=wpa2-psk passphrase=$WifiPass
        } else={
            /interface/wifi/security set [find name="hifastlink-sec"] authentication-types=wpa2-psk passphrase=$WifiPass
        }
        :if ([:len [/interface/wifi/configuration find name="hifastlink-wifi"]] = 0) do={
            /interface/wifi/configuration add name="hifastlink-wifi" ssid=$WifiSSID security="hifastlink-sec" mode=ap
        } else={
            /interface/wifi/configuration set [find name="hifastlink-wifi"] ssid=$WifiSSID security="hifastlink-sec"
        }
    } else={
        :if ([:len [/interface/wifi/configuration find name="hifastlink-wifi"]] = 0) do={
            /interface/wifi/configuration add name="hifastlink-wifi" ssid=$WifiSSID mode=ap
        } else={
            /interface/wifi/configuration set [find name="hifastlink-wifi"] ssid=$WifiSSID
        }
    }
    /interface/wifi set [find] configuration="hifastlink-wifi" disabled=no
    :put (">> WiFi enabled: " . $wifiIface)
} else={
    :local wlanList [/interface/wireless find]
    :if ([:len $wlanList] > 0) do={
        :set wifiIface [/interface/wireless get ($wlanList->0) name]
        :if ($WifiPass != "") do={
            /interface/wireless set [find] disabled=no mode=ap-bridge ssid=$WifiSSID security-profile=default
            /interface/wireless/security-profiles set [find name=default] mode=dynamic-keys authentication-types=wpa2-psk wpa2-pre-shared-key=$WifiPass
        } else={
            /interface/wireless set [find] disabled=no mode=ap-bridge ssid=$WifiSSID security-profile=default
            /interface/wireless/security-profiles set [find name=default] mode=none
        }
        :put (">> Wireless enabled: " . $wifiIface)
    }
}

# --------------------------------------------------
# 0c. WAN / NAT / DHCP CLIENT
# --------------------------------------------------

# Create WAN interface list
:if ([:len [/interface/list find name="WAN"]] = 0) do={
    /interface/list add name="WAN"
    :put ">> WAN interface list created"
}
:if ([:len [/interface/list/member find list="WAN" interface="ether1"]] = 0) do={
    /interface/list/member add list="WAN" interface="ether1"
    :put ">> ether1 added to WAN list"
}

# NAT masquerade
:if ([:len [/ip/firewall/nat find chain=srcnat action=masquerade]] = 0) do={
    /ip/firewall/nat add chain=srcnat action=masquerade out-interface-list=WAN comment="HiFastLink NAT"
    :put ">> NAT masquerade added"
} else={
    :put ">> NAT masquerade already exists"
}

# Clear any static IP on ether1, then ensure DHCP client runs
:do { /ip/address remove [find interface="ether1"] } on-error={}
:if ([:len [/ip/dhcp-client find interface="ether1"]] = 0) do={
    /ip/dhcp-client add interface=ether1 disabled=no
    :put ">> DHCP client added on ether1"
} else={
    /ip/dhcp-client set [find interface="ether1"] disabled=no
    :put ">> DHCP client enabled on ether1"
}

# --------------------------------------------------
# 1. IDENTITY
# --------------------------------------------------

/system/identity set name=$LocationName
:put ">> Identity set"

# --------------------------------------------------
# 2. WIREGUARD VPN
# --------------------------------------------------

:if ($VPNEnabled) do={
    :put ">> Configuring WireGuard VPN..."

    :do { /interface/wireguard remove [find name="wg-saas"] } on-error={}
    /interface/wireguard add name="wg-saas" listen-port=$WGListenPort private-key=$WGRouterPrivateKey

    :do { /ip/address remove [find interface="wg-saas"] } on-error={}
    /ip/address add address=($WGRouterIP . "/24") interface="wg-saas" network="192.168.42.0"

    # Remove all peers before re-adding to avoid duplicates
    :do { /interface/wireguard/peers remove [find] } on-error={}
    /interface/wireguard/peers add interface="wg-saas" \
        public-key=$WGServerPublicKey \
        endpoint-address=$WGServerEndpoint \
        endpoint-port=$WGServerPort \
        allowed-address=($WGServerIP . "/32") \
        persistent-keepalive=25s

    :delay 5s
    :put ">> WireGuard VPN configured"
}

# --------------------------------------------------
# 3. RADIUS
# --------------------------------------------------

:local RadiusIP
:if ($VPNEnabled) do={
    :set RadiusIP $WGServerIP
} else={
    :set RadiusIP "142.93.47.189"
}

/radius remove [find dynamic=no]
/radius add address=$RadiusIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
:put ">> RADIUS configured"

# --------------------------------------------------
# 4. HOTSPOT - evict conflicting servers first
# --------------------------------------------------

# Remove any hotspot servers NOT on our bridge to avoid profile/RADIUS conflicts
:foreach hs in=[/ip/hotspot find] do={
    :if ([/ip/hotspot get $hs interface] != $BridgeName) do={
        /ip/hotspot remove $hs
        :put ">> Removed conflicting hotspot on other interface"
    }
}

# Hotspot Profile
:if ([:len [/ip/hotspot/profile find name="hifastlink"]] = 0) do={
    /ip/hotspot/profile add name="hifastlink" dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
    :put ">> Hotspot profile created"
} else={
    /ip/hotspot/profile set [find name="hifastlink"] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
    :put ">> Hotspot profile updated"
}

# Hotspot Server
:if ([:len [/ip/hotspot find interface=$BridgeName]] = 0) do={
    /ip/hotspot add name="hifastlink" interface=$BridgeName profile="hifastlink" address-pool="hs-pool" disabled=no
    :put ">> Hotspot server created"
} else={
    /ip/hotspot set [find interface=$BridgeName] profile="hifastlink" address-pool="hs-pool" disabled=no
    :put ">> Hotspot server updated"
}

# Clear stale IP bindings that intercept clients before the captive portal
/ip/hotspot/ip-binding remove [find]
:put ">> IP bindings cleared"

# --------------------------------------------------
# 5. USER PROFILE
# --------------------------------------------------

:if ([:len [/ip/hotspot/user/profile find name="default" dynamic=no]] = 0) do={
    :do { /ip/hotspot/user/profile add name="default" shared-users=10 } on-error={ :put ">> User profile add skipped (exists)" }
} else={
    /ip/hotspot/user/profile set [find name="default" dynamic=no] shared-users=10
}

# Remove default local admin user - prevents RADIUS bypass
:do { /ip/hotspot/user remove [find name="admin"] } on-error={}
:put ">> User profile configured"

# --------------------------------------------------
# 6. WALLED GARDEN - DNS
# --------------------------------------------------

/ip/hotspot/walled-garden remove [find dynamic=no]
/ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard Subdomains"
/ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
/ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Allow Paystack"
/ip/hotspot/walled-garden add dst-host="*.paystack.co" comment="Allow Paystack Alt"
/ip/hotspot/walled-garden add dst-host="*.sentry.io" comment="Allow Error Logs"
:put ">> Walled Garden (DNS) configured"

# --------------------------------------------------
# 7. WALLED GARDEN - IP
# --------------------------------------------------

/ip/hotspot/walled-garden/ip remove [find dynamic=no]
/ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP comment="HiFastLink Server"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=$WebsiteIP comment="HTTP"
/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment="DNS"
:put ">> Walled Garden (IP) configured"

# --------------------------------------------------
# 8. DNS
# --------------------------------------------------

/ip/dns set servers=192.168.88.1 allow-remote-requests=yes

:local bridgeAddrFull [/ip/address get [find interface=$BridgeName] address]
:local bridgeIP [:pick $bridgeAddrFull 0 [:find $bridgeAddrFull "/"]]
:if ([:len [/ip/dns/static find name=$DNSName dynamic=no]] > 0) do={
    /ip/dns/static set [find name=$DNSName dynamic=no] address=$bridgeIP ttl=1m
    :put (">> Static DNS updated: " . $DNSName . " -> " . $bridgeIP)
} else={
    :do {
        /ip/dns/static add name=$DNSName address=$bridgeIP ttl=1m
        :put (">> Static DNS created: " . $DNSName . " -> " . $bridgeIP)
    } on-error={
        :put (">> DNS for " . $DNSName . " managed by hotspot dynamically, skipping")
    }
}
:put ">> DNS configured"

# --------------------------------------------------
# 9. HEARTBEAT SCHEDULER
# --------------------------------------------------

:local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
/system/scheduler remove [find name="heartbeat"]
/system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"" . $heartbeatURL . "\" mode=https output=none")
:put ">> Heartbeat configured"

# --------------------------------------------------
# 10. REALTIME SPEED REPORTER
# --------------------------------------------------

/system/scheduler remove [find name="realtime-stats"]
:do { /system/script remove [find name="realtime-stats-script"] } on-error={}
/system/script add name="realtime-stats-script" source=":local identity [/system/identity get name]; :local apiURL \"https://hifastlink.com/api/routers/speed\"; :foreach session in=[/ip/hotspot/active find] do={:local user [/ip/hotspot/active get \$session user]; :local bytesIn [/ip/hotspot/active get \$session bytes-in]; :local bytesOut [/ip/hotspot/active get \$session bytes-out]; :local fullURL (\$apiURL . \"?identity=\" . \$identity . \"&user=\" . \$user . \"&bytes_in=\" . \$bytesIn . \"&bytes_out=\" . \$bytesOut); :do {/tool/fetch url=\$fullURL mode=https output=none} on-error={}}"
/system/scheduler add name="realtime-stats" interval=10s on-event="/system/script run realtime-stats-script"
:put ">> Speed reporter configured"

# --------------------------------------------------
# 11. NTP
# --------------------------------------------------

/system/ntp/client set enabled=yes
:do { /system/ntp/client/servers remove [find address=162.159.200.1] } on-error={}
:do { /system/ntp/client/servers remove [find address=162.159.200.123] } on-error={}
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123
:put ">> NTP configured"

# --------------------------------------------------
# 12. API SERVICE
# --------------------------------------------------

/ip/service set api disabled=no port=8728
:put ">> API enabled"

# --------------------------------------------------
:put "========================================"
:put ("   SETUP COMPLETE: " . $LocationName)
:put ("   Login: http://" . $DNSName)
:put ("   Hotspot on: " . $BridgeName)
:if ($VPNEnabled) do={
    :put "   VPN: WireGuard Enabled"
    :put ("   VPN IP: " . $WGRouterIP)
}
:put "   READY TO DEPLOY"
:put "========================================"
RSC;

        $script = str_replace([
            '{LOCATION}', '{DOMAIN}', '{DNSNAME}', '{BRIDGE}', '{WEBSITEIP}',
            '{WG_SERVER_PUB_KEY}', '{WG_ROUTER_PRIV_KEY}', '{WG_SERVER_ENDPOINT}',
            '{WG_SERVER_PORT}', '{WG_LISTEN_PORT}', '{WG_ROUTER_IP}', '{WG_SERVER_IP}',
            '{SECRET}', '{WIFI_SSID}', '{WIFI_PASS}',
        ], [
            $escLocation, $escDomain, $escDns, $escBridge, $escWebsiteIp,
            $escWgServerPubKey, $escWgRouterPrivKey, $escWgServerEndpoint,
            $escWgServerPort, $escWgListenPort, $escWgRouterIp, $escWgServerIp,
            $escSecret, $escWifiSsid, $escWifiPassword,
        ], $template);

        $filename = 'router-' . ($router->nas_identifier ?: $router->id) . '.rsc';

        return response()->streamDownload(function () use ($script) {
            echo $script;
        }, $filename, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}