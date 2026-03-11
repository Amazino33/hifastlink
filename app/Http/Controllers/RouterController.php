<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Router;

class RouterController extends Controller
{
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
                    $q->where('data_remaining', '>', 0)->orWhereNull('data_limit');
                })
                ->orderBy('expires_at', 'desc')
                ->first();
        } else {
            $hasExpiry     = $user->plan_expiry && $user->plan_expiry->isFuture();
            $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));
            if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
                $validSubscription = (object)['plan_id' => $user->plan_id, 'expires_at' => $user->plan_expiry];
            }
        }

        if (!$validSubscription) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        if (empty($user->username) || empty($user->radius_password)) {
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        $gateway  = env('MIKROTIK_GATEWAY', 'http://login.wifi/login');
        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (!preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        return response()->json([
            'username'      => $user->username,
            'password'      => $user->radius_password,
            'login_url'     => $loginUrl,
            'dashboard_url' => route('dashboard'),
        ]);
    }

    public function bridgeLogin(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        if (empty($user->username) || empty($user->radius_password)) {
            return response()->json(['message' => 'User credentials missing.'], 500);
        }

        $mac       = $request->input('mac');
        $ip        = $request->input('ip');
        $linkLogin = $request->input('link-login') ?? $request->input('link-login-only') ?? $request->input('link-orig');
        $bridgeUrl = rtrim(env('RADIUS_BRIDGE_URL', ''), '/');
        $secret    = env('RADIUS_SECRET_KEY');

        if ($bridgeUrl && $secret) {
            try {
                $resp = \Illuminate\Support\Facades\Http::post($bridgeUrl . '/login', array_filter([
                    'username' => $user->username,
                    'password' => $user->radius_password,
                    'secret'   => $secret,
                    'mac'      => $mac,
                    'ip'       => $ip,
                    'link'     => $linkLogin,
                ]));
                if ($resp->successful()) {
                    return response()->json(['success' => true, 'redirect' => $linkLogin ?? null]);
                }
            } catch (\Exception $e) {
                Log::error('Bridge login error: ' . $e->getMessage());
            }
        }

        $fallbackUrl = env('MIKROTIK_GATEWAY', 'http://login.wifi/login');
        if (strpos($fallbackUrl, '://') === false) $fallbackUrl = 'http://' . $fallbackUrl;
        if (!preg_match('#/login#', $fallbackUrl)) $fallbackUrl = rtrim($fallbackUrl, '/') . '/login';

        return response()->json([
            'success'       => false,
            'username'      => $user->username,
            'password'      => $user->radius_password,
            'login_url'     => $fallbackUrl,
            'dashboard_url' => route('dashboard'),
            'redirect'      => $linkLogin ?? null,
        ]);
    }

    public function downloadConfig(Router $router)
    {
        $location   = $router->nas_identifier ?: $router->name;
        $secret     = $router->secret;
        $appUrl     = rtrim(config('app.url', env('APP_URL', 'https://hifastlink.com')), '/');
        $domain     = parse_url($appUrl, PHP_URL_HOST) ?: preg_replace('#https?://#', '', $appUrl);
        $dnsName    = 'login.wifi';
        $bridgeName = 'bridge';
        $websiteIp  = env('WEBSITE_IP', '194.36.184.49');

        $wgServerPubKey   = env('WG_SERVER_PUBLIC_KEY', 'INSERT_SERVER_PUBLIC_KEY_HERE');
        $wgServerEndpoint = env('WG_SERVER_ENDPOINT', env('RADIUS_PUBLIC_IP', 'INSERT_LINUX_SERVER_PUBLIC_IP_HERE'));
        $wgServerPort     = env('WG_SERVER_PORT', '51820');
        $wgListenPort     = env('WG_LISTEN_PORT', '13231');
        $wgRouterIp       = $router->vpn_ip ?? '192.168.42.10';
        $wgServerIp       = env('WG_SERVER_IP', '192.168.42.1');
        $wgRouterPrivKey  = $router->wireguard_private_key ?? '';
        $wifiSsid         = $router->wifi_ssid ?? 'HiFastLink';
        $wifiPassword     = $router->wifi_password ?? '';

        $esc = fn($v) => str_replace('"', '\\"', $v);

        $template = <<<'RSC'
# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT
#  Author: Gem (The Developer)
# ==================================================

:global LocationName "{LOCATION}"
:global DomainName   "{DOMAIN}"
:global DNSName      "{DNSNAME}"
:global BridgeName   "{BRIDGE}"
:global WebsiteIP    "{WEBSITEIP}"
:global VPNEnabled         true
:global WGServerPublicKey  "{WG_SERVER_PUB_KEY}"
:global WGRouterPrivateKey "{WG_ROUTER_PRIV_KEY}"
:global WGServerEndpoint   "{WG_SERVER_ENDPOINT}"
:global WGServerPort       "{WG_SERVER_PORT}"
:global WGListenPort       "{WG_LISTEN_PORT}"
:global WGRouterIP         "{WG_ROUTER_IP}"
:global WGServerIP         "{WG_SERVER_IP}"
:global WifiSSID           "{WIFI_SSID}"
:global WifiPass           "{WIFI_PASS}"
:global RadiusSecret       "{SECRET}"

# ==================================================
# v7 DIRECT SETUP
# ==================================================

:put (">> Starting setup for " . $LocationName . "...")

# -------------------------------------------------------
# STEP 0 - NUCLEAR FIREWALL CLEANUP
# Remove ALL existing non-dynamic rules. This guarantees
# no stale rules from previous runs or factory defaults
# can block or take priority over our new ruleset.
# -------------------------------------------------------
:put ">> Wiping all existing firewall rules..."
/ip/firewall/filter remove [find dynamic=no]
/ip/firewall/nat    remove [find dynamic=no]
/ip/firewall/mangle remove [find dynamic=no]
:put ">> Firewall wiped"

# -------------------------------------------------------
# STEP 1 - BRIDGE
# -------------------------------------------------------
:if ([:len [/interface/bridge find name=$BridgeName]] = 0) do={
    /interface/bridge add name=$BridgeName protocol-mode=rstp
}
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
}
:if ([:len [/ip/address find interface=$BridgeName]] = 0) do={
    /ip/address add address="192.168.88.1/24" interface=$BridgeName
}
:if ([:len [/ip/dhcp-server find interface=$BridgeName]] = 0) do={
    :if ([:len [/ip/pool find name="hs-pool"]] = 0) do={
        /ip/pool add name="hs-pool" ranges="192.168.88.10-192.168.88.254"
    }
    /ip/dhcp-server add name="defconf" interface=$BridgeName address-pool="hs-pool" disabled=no
    :do { /ip/dhcp-server/network remove [find address="192.168.88.0/24"] } on-error={}
    /ip/dhcp-server/network add address="192.168.88.0/24" gateway="192.168.88.1" dns-server="192.168.88.1"
} else={
    /ip/dhcp-server set [find interface=$BridgeName] address-pool=hs-pool
}
:put ">> Bridge ready"

# -------------------------------------------------------
# STEP 2 - WIFI
# -------------------------------------------------------
:local wifiList [/interface/wifi find]
:if ([:len $wifiList] > 0) do={
    :if ($WifiPass != "") do={
        :if ([:len [/interface/wifi/security find name="hifastlink-sec"]] = 0) do={
            /interface/wifi/security add name="hifastlink-sec" authentication-types=wpa2-psk passphrase=$WifiPass
        } else={
            /interface/wifi/security set [find name="hifastlink-sec"] passphrase=$WifiPass
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
} else={
    :local wlanList [/interface/wireless find]
    :if ([:len $wlanList] > 0) do={
        :if ($WifiPass != "") do={
            /interface/wireless set [find] disabled=no mode=ap-bridge ssid=$WifiSSID security-profile=default
            /interface/wireless/security-profiles set [find name=default] mode=dynamic-keys authentication-types=wpa2-psk wpa2-pre-shared-key=$WifiPass
        } else={
            /interface/wireless set [find] disabled=no mode=ap-bridge ssid=$WifiSSID security-profile=default
            /interface/wireless/security-profiles set [find name=default] mode=none
        }
    }
}
:put ">> WiFi ready"

# -------------------------------------------------------
# STEP 3 - WAN + ether1 DHCP
# -------------------------------------------------------
:if ([:len [/interface/list find name="WAN"]] = 0) do={
    /interface/list add name="WAN"
}
:if ([:len [/interface/list/member find list="WAN" interface="ether1"]] = 0) do={
    /interface/list/member add list="WAN" interface="ether1"
}
:do { /ip/address remove [find interface="ether1"] } on-error={}
:if ([:len [/ip/dhcp-client find interface="ether1"]] = 0) do={
    /ip/dhcp-client add interface=ether1 disabled=no
} else={
    /ip/dhcp-client set [find interface="ether1"] disabled=no
}
:put ">> WAN ready"

# -------------------------------------------------------
# STEP 4 - REBUILD FIREWALL FROM SCRATCH
#
# Since we wiped everything above, we simply add rules
# in the order we want them to appear top-to-bottom.
# No stale rules can interfere.
#
# INPUT chain  - protects the router itself
# FORWARD chain - controls client internet access
# -------------------------------------------------------

# NAT
/ip/firewall/nat add chain=srcnat action=masquerade out-interface-list=WAN comment="HiFastLink NAT"
:put ">> NAT added"

# INPUT chain
# CRITICAL ORDER: established,related,untracked MUST come before drop invalid.
# RouterOS conntrack can briefly mark valid return packets as invalid under load
# or after a reset. Accepting established first prevents them being wrongly dropped.
/ip/firewall/filter add chain=input action=accept connection-state=established,related,untracked comment="HiFastLink Input Established"
/ip/firewall/filter add chain=input action=drop   connection-state=invalid comment="HiFastLink Input Drop Invalid"
# Allow WireGuard VPN from WAN (needed for RADIUS auth)
/ip/firewall/filter add chain=input action=accept in-interface-list=WAN protocol=udp dst-port=$WGListenPort comment="HiFastLink Input WireGuard"
# Allow DHCP, DNS, hotspot ports from bridge clients
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=udp dst-port=53  comment="HiFastLink Input DNS UDP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=53  comment="HiFastLink Input DNS TCP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=udp dst-port=67  comment="HiFastLink Input DHCP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=80  comment="HiFastLink Input Hotspot Login"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=8080 comment="HiFastLink Input Hotspot HTTP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=2258 comment="HiFastLink Input Hotspot Proxy"
:put ">> INPUT rules added"

# FORWARD chain
# CRITICAL ORDER:
#   1. established,related,untracked - return traffic for active sessions (MUST be first)
#   2. drop invalid                  - drop bad packets AFTER accepting valid returns
#   3. hotspot=auth                  - new outbound traffic from authenticated clients only
#   4. drop (default)                - block everyone else, enforcing the captive portal
#
# Why established before invalid?
#   Conntrack can mark a legitimate return packet as "invalid" if the session
#   table is under pressure or was recently reset. If we drop invalid first,
#   authenticated users lose internet mid-session. Accepting established first
#   guarantees in-progress sessions are never interrupted.
/ip/firewall/filter add chain=forward action=accept connection-state=established,related,untracked comment="HiFastLink Forward Established"
/ip/firewall/filter add chain=forward action=drop   connection-state=invalid comment="HiFastLink Forward Drop Invalid"
/ip/firewall/filter add chain=forward action=accept hotspot=auth comment="HiFastLink Forward Auth"
/ip/firewall/filter add chain=forward action=drop comment="HiFastLink Forward Drop Default"
:put ">> FORWARD rules added"
:put ">> Firewall rebuilt - clean state confirmed"

# -------------------------------------------------------
# STEP 5 - WIREGUARD VPN
# -------------------------------------------------------
:if ($VPNEnabled) do={
    :do { /interface/wireguard remove [find name="wg-saas"] } on-error={}
    /interface/wireguard add name="wg-saas" listen-port=$WGListenPort private-key=$WGRouterPrivateKey
    :do { /ip/address remove [find interface="wg-saas"] } on-error={}
    /ip/address add address=($WGRouterIP . "/24") interface="wg-saas" network="192.168.42.0"
    :do { /interface/wireguard/peers remove [find] } on-error={}
    /interface/wireguard/peers add interface="wg-saas" public-key=$WGServerPublicKey endpoint-address=$WGServerEndpoint endpoint-port=$WGServerPort allowed-address=($WGServerIP . "/32") persistent-keepalive=25s
    :delay 5s
    :put ">> WireGuard ready"
}

# -------------------------------------------------------
# STEP 6 - RADIUS
# -------------------------------------------------------
:local RadiusIP
:if ($VPNEnabled) do={ :set RadiusIP $WGServerIP } else={ :set RadiusIP "142.93.47.189" }
/radius remove [find dynamic=no]
/radius add address=$RadiusIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
:put ">> RADIUS ready"

# -------------------------------------------------------
# STEP 7 - HOTSPOT
# -------------------------------------------------------
:if ([:len [/ip/pool find name="hs-pool"]] = 0) do={
    /ip/pool add name="hs-pool" ranges="192.168.88.10-192.168.88.254"
}
/ip/dhcp-server set [find interface=$BridgeName] address-pool=hs-pool
:if ([:len [/ip/hotspot/profile find name="hifastlink"]] = 0) do={
    /ip/hotspot/profile add name="hifastlink" dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
} else={
    /ip/hotspot/profile set [find name="hifastlink"] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
}
:foreach hs in=[/ip/hotspot find] do={
    :if ([/ip/hotspot get $hs interface] != $BridgeName) do={ /ip/hotspot remove $hs }
}
:if ([:len [/ip/hotspot find interface=$BridgeName]] = 0) do={
    /ip/hotspot add name="hifastlink" interface=$BridgeName profile="hifastlink" address-pool="hs-pool" disabled=no
} else={
    /ip/hotspot set [find interface=$BridgeName] profile="hifastlink" address-pool="hs-pool" disabled=no
}
/ip/hotspot/ip-binding remove [find]
:if ([:len [/ip/hotspot/user/profile find name="default" dynamic=no]] = 0) do={
    :do { /ip/hotspot/user/profile add name="default" shared-users=10 } on-error={}
} else={
    /ip/hotspot/user/profile set [find name="default" dynamic=no] shared-users=10
}
:do { /ip/hotspot/user remove [find name="admin"] } on-error={}
:put ">> Hotspot ready"

# -------------------------------------------------------
# STEP 8 - WALLED GARDEN
# -------------------------------------------------------
/ip/hotspot/walled-garden remove [find dynamic=no]
/ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard Subdomains"
/ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
/ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Allow Paystack"
/ip/hotspot/walled-garden add dst-host="*.paystack.co"  comment="Allow Paystack Alt"
/ip/hotspot/walled-garden add dst-host="*.sentry.io"    comment="Allow Error Logs"
/ip/hotspot/walled-garden/ip remove [find dynamic=no]
/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53 comment="DNS"
/ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP comment="HiFastLink Server"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS HiFastLink"
/ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80  dst-address=$WebsiteIP comment="HTTP HiFastLink"
:put ">> Walled Garden ready"

# -------------------------------------------------------
# STEP 9 - DNS
# -------------------------------------------------------
# IMPORTANT: servers must be public upstream resolvers, NOT the router's own IP.
# Setting servers=192.168.88.1 (self) causes an infinite DNS loop - the router
# asks itself, which asks itself, and all DNS queries fail. Clients then cannot
# resolve login.wifi so the captive portal never appears.
/ip/dns set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes
:local bridgeAddrFull [/ip/address get [find interface=$BridgeName] address]
:local bridgeIP [:pick $bridgeAddrFull 0 [:find $bridgeAddrFull "/"]]
# Static entry so login.wifi resolves to this router's bridge IP for the captive portal
:if ([:len [/ip/dns/static find name=$DNSName dynamic=no]] > 0) do={
    /ip/dns/static set [find name=$DNSName dynamic=no] address=$bridgeIP ttl=1m
} else={
    :do { /ip/dns/static add name=$DNSName address=$bridgeIP ttl=1m } on-error={}
}
:put ">> DNS ready"

# -------------------------------------------------------
# STEP 10 - IDENTITY, HEARTBEAT, STATS, NTP, SERVICES
# -------------------------------------------------------
/system/identity set name=$LocationName
:local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
/system/scheduler remove [find name="heartbeat"]
/system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"$heartbeatURL\" mode=https output=none")
/system/scheduler remove [find name="realtime-stats"]
:do { /system/script remove [find name="realtime-stats-script"] } on-error={}
/system/script add name="realtime-stats-script" source=":local identity [/system/identity get name]; :local apiURL \"https://hifastlink.com/api/routers/speed\"; :foreach session in=[/ip/hotspot/active find] do={:local user [/ip/hotspot/active get \$session user]; :local bytesIn [/ip/hotspot/active get \$session bytes-in]; :local bytesOut [/ip/hotspot/active get \$session bytes-out]; :local fullURL (\$apiURL . \"?identity=\" . \$identity . \"&user=\" . \$user . \"&bytes_in=\" . \$bytesIn . \"&bytes_out=\" . \$bytesOut); :do {/tool/fetch url=\$fullURL mode=https output=none} on-error={}}"
/system/scheduler add name="realtime-stats" interval=10s on-event="/system/script run realtime-stats-script"
/system/ntp/client set enabled=yes
:do {/system/ntp/client/servers remove [find address=162.159.200.1]}   on-error={}
:do {/system/ntp/client/servers remove [find address=162.159.200.123]} on-error={}
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123
/ip/service set api disabled=no port=8728
/ip/service set www-ssl disabled=yes
:put ">> System config ready"

:put "========================================"
:put ("   COMPLETE: " . $LocationName)
:put ("   Login:    http://" . $DNSName)
:if ($VPNEnabled) do={
    :put ("   VPN IP:   " . $WGRouterIP)
}
:put "   Rebooting in 5 seconds..."
:put "========================================"
:delay 5s
/system/reboot
RSC;

        $script = str_replace([
            '{LOCATION}', '{DOMAIN}', '{DNSNAME}', '{BRIDGE}', '{WEBSITEIP}',
            '{WG_SERVER_PUB_KEY}', '{WG_SERVER_ENDPOINT}', '{WG_SERVER_PORT}',
            '{WG_LISTEN_PORT}', '{WG_ROUTER_IP}', '{WG_SERVER_IP}', '{SECRET}',
            '{WG_ROUTER_PRIV_KEY}', '{WIFI_SSID}', '{WIFI_PASS}',
        ], [
            $esc($location), $esc($domain), $esc($dnsName), $esc($bridgeName), $esc($websiteIp),
            $esc($wgServerPubKey), $esc($wgServerEndpoint), $esc($wgServerPort),
            $esc($wgListenPort), $esc($wgRouterIp), $esc($wgServerIp), $esc($secret),
            $esc($wgRouterPrivKey), $esc($wifiSsid), $esc($wifiPassword),
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