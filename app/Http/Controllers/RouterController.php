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

:put (">> HiFastLink setup starting for: " . $LocationName)

# =======================================================
# STEP 0 - WIPE FIREWALL
# Remove every non-dynamic rule so nothing from previous
# configs or factory defaults interferes.
# =======================================================
:put ">> Wiping firewall..."
/ip/firewall/filter remove [find dynamic=no]
/ip/firewall/nat    remove [find dynamic=no]
/ip/firewall/mangle remove [find dynamic=no]
:put ">> Firewall wiped"

# =======================================================
# STEP 1 - HOTSPOT (disable before touching interfaces)
# Must disable hotspot first or RouterOS refuses to change
# bridge ports / addresses while the hotspot is running.
# =======================================================
:put ">> Disabling existing hotspot servers..."
/ip/hotspot set [find] disabled=yes
:delay 2s

# =======================================================
# STEP 2 - BRIDGE + PORTS
# Wipe all bridge ports, then rebuild clean.
# =======================================================
:put ">> Rebuilding bridge..."
:if ([:len [/interface/bridge find name=$BridgeName]] = 0) do={
    /interface/bridge add name=$BridgeName protocol-mode=rstp comment="HiFastLink Bridge"
}
# Remove ALL existing bridge ports so none linger from old config
/interface/bridge/port remove [find bridge=$BridgeName]
# Add ethernet ports (skip ether1 - that is WAN)
:foreach port in={"ether2";"ether3";"ether4";"ether5"} do={
    :if ([:len [/interface find name=$port]] > 0) do={
        /interface/bridge/port add interface=$port bridge=$BridgeName
    }
}
# Add WiFi to bridge
:local wifiIface ""
:if ([:len [/interface find name="wifi1"]]  > 0) do={ :set wifiIface "wifi1" }
:if ([:len [/interface find name="wlan1"]]  > 0) do={ :set wifiIface "wlan1" }
:if ($wifiIface != "") do={
    /interface/bridge/port add interface=$wifiIface bridge=$BridgeName
    :put (">> Added " . $wifiIface . " to bridge")
}
# Wipe all IP addresses on bridge, then add ours fresh
/ip/address remove [find interface=$BridgeName]
/ip/address add address="192.168.88.1/24" interface=$BridgeName comment="HiFastLink Bridge IP"
:put ">> Bridge IP set: 192.168.88.1/24"

# =======================================================
# STEP 3 - DHCP SERVER
# Remove all existing DHCP config for this subnet, rebuild.
# =======================================================
:put ">> Rebuilding DHCP server..."
# Wipe DHCP servers on this bridge
/ip/dhcp-server remove [find interface=$BridgeName]
# Wipe the IP pool
/ip/pool remove [find name="hs-pool"]
# Wipe the network entry for our subnet
/ip/dhcp-server/network remove [find address="192.168.88.0/24"]
# Recreate clean
/ip/pool add name="hs-pool" ranges="192.168.88.10-192.168.88.254"
/ip/dhcp-server add name="hifastlink-dhcp" interface=$BridgeName address-pool="hs-pool" disabled=no comment="HiFastLink DHCP"
/ip/dhcp-server/network add address="192.168.88.0/24" gateway="192.168.88.1" dns-server="192.168.88.1" comment="HiFastLink Network"
:put ">> DHCP server ready"

# =======================================================
# STEP 4 - WAN (ether1)
# Remove static IPs on ether1, ensure DHCP client is on.
# =======================================================
:put ">> Configuring WAN..."
:if ([:len [/interface/list find name="WAN"]] = 0) do={
    /interface/list add name="WAN"
}
:if ([:len [/interface/list/member find list="WAN" interface="ether1"]] = 0) do={
    /interface/list/member add list="WAN" interface="ether1"
}
# Remove any static IPs on ether1
/ip/address remove [find interface="ether1"]
# Wipe and recreate DHCP client to force a fresh lease
/ip/dhcp-client remove [find interface="ether1"]
/ip/dhcp-client add interface=ether1 disabled=no comment="HiFastLink WAN"
:put ">> WAN ready"

# =======================================================
# STEP 5 - WIFI
# =======================================================
:put ">> Configuring WiFi..."
:local wifiList [/interface/wifi find]
:if ([:len $wifiList] > 0) do={
    # RouterOS v7 wifi
    /interface/wifi/security    remove [find name="hifastlink-sec"]
    /interface/wifi/configuration remove [find name="hifastlink-wifi"]
    :if ($WifiPass != "") do={
        /interface/wifi/security      add name="hifastlink-sec"  authentication-types=wpa2-psk passphrase=$WifiPass
        /interface/wifi/configuration add name="hifastlink-wifi" ssid=$WifiSSID security="hifastlink-sec" mode=ap
    } else={
        /interface/wifi/configuration add name="hifastlink-wifi" ssid=$WifiSSID mode=ap
    }
    /interface/wifi set [find] configuration="hifastlink-wifi" disabled=no
    :put ">> WiFi (v7) configured"
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
        :put ">> WiFi (wireless) configured"
    }
}

# =======================================================
# STEP 6 - WIREGUARD VPN
# =======================================================
:put ">> Configuring WireGuard VPN..."
# Remove old wg interface entirely and recreate
:do { /interface/wireguard remove [find name="wg-saas"] } on-error={}
/interface/wireguard add name="wg-saas" listen-port=$WGListenPort private-key=$WGRouterPrivateKey comment="HiFastLink VPN"
/ip/address remove [find interface="wg-saas"]
/ip/address add address=($WGRouterIP . "/24") interface="wg-saas" network="192.168.42.0" comment="HiFastLink VPN IP"
/interface/wireguard/peers remove [find interface="wg-saas"]
/interface/wireguard/peers add interface="wg-saas" public-key=$WGServerPublicKey endpoint-address=$WGServerEndpoint endpoint-port=$WGServerPort allowed-address=($WGServerIP . "/32") persistent-keepalive=25s comment="HiFastLink VPN Peer"
:delay 5s
:put ">> WireGuard ready"

# =======================================================
# STEP 7 - FIREWALL (build fresh after wipe in Step 0)
#
# Rule order is critical:
# INPUT:   established -> drop invalid -> specific accepts
# FORWARD: established -> drop invalid -> hotspot=auth -> default drop
#
# The final "drop" in FORWARD is what enforces the captive
# portal. Without it RouterOS passes all traffic (policy=accept).
# =======================================================
:put ">> Building firewall rules..."

# NAT - masquerade client traffic going out WAN
/ip/firewall/nat add chain=srcnat action=masquerade out-interface-list=WAN comment="HiFastLink NAT"

# INPUT chain
/ip/firewall/filter add chain=input action=accept connection-state=established,related,untracked comment="HiFastLink In: Established"
/ip/firewall/filter add chain=input action=drop   connection-state=invalid                       comment="HiFastLink In: Drop Invalid"
/ip/firewall/filter add chain=input action=accept in-interface-list=WAN    protocol=udp dst-port=$WGListenPort comment="HiFastLink In: WireGuard"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=udp dst-port=53  comment="HiFastLink In: DNS UDP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=53  comment="HiFastLink In: DNS TCP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=udp dst-port=67  comment="HiFastLink In: DHCP"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=80  comment="HiFastLink In: HTTP (hotspot)"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=8080 comment="HiFastLink In: Hotspot proxy"
/ip/firewall/filter add chain=input action=accept in-interface=$BridgeName protocol=tcp dst-port=2258 comment="HiFastLink In: Hotspot alt"

# FORWARD chain
/ip/firewall/filter add chain=forward action=accept connection-state=established,related,untracked comment="HiFastLink Fwd: Established"
/ip/firewall/filter add chain=forward action=drop   connection-state=invalid                       comment="HiFastLink Fwd: Drop Invalid"
/ip/firewall/filter add chain=forward action=accept hotspot=auth                                   comment="HiFastLink Fwd: Hotspot Auth"
/ip/firewall/filter add chain=forward action=drop                                                  comment="HiFastLink Fwd: Default Drop"

:put ">> Firewall ready"

# =======================================================
# STEP 8 - RADIUS
# =======================================================
:put ">> Configuring RADIUS..."
/radius remove [find dynamic=no]
:local RadiusIP
:if ($VPNEnabled) do={ :set RadiusIP $WGServerIP } else={ :set RadiusIP "142.93.47.189" }
/radius add address=$RadiusIP secret=$RadiusSecret service=hotspot timeout=3000ms comment="HiFastLink RADIUS"
:put ">> RADIUS ready"

# =======================================================
# STEP 9 - HOTSPOT
# Wipe all existing hotspot config and rebuild from scratch.
# =======================================================
:put ">> Rebuilding hotspot..."
# Wipe all hotspot servers, profiles, users (except dynamic)
/ip/hotspot        remove [find dynamic=no]
/ip/hotspot/profile remove [find name!="default" dynamic=no]
/ip/hotspot/user    remove [find dynamic=no]
/ip/hotspot/ip-binding remove [find]

# Create profile
/ip/hotspot/profile add name="hifastlink" dns-name=$DNSName use-radius=yes login-by=cookie,http-chap,http-pap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m comment="HiFastLink Profile"

# Create hotspot server on bridge
/ip/hotspot add name="hifastlink" interface=$BridgeName profile="hifastlink" address-pool="hs-pool" disabled=no comment="HiFastLink Hotspot"

# Hotspot user profile (RADIUS handles auth but this sets defaults)
:if ([:len [/ip/hotspot/user/profile find name="default" dynamic=no]] = 0) do={
    :do { /ip/hotspot/user/profile add name="default" shared-users=10 } on-error={}
} else={
    /ip/hotspot/user/profile set [find name="default" dynamic=no] shared-users=10
}
:put ">> Hotspot ready"

# =======================================================
# STEP 10 - WALLED GARDEN
# Wipe and rebuild. These allow unauthenticated clients to
# reach the login page and payment portal only.
# =======================================================
:put ">> Rebuilding walled garden..."
/ip/hotspot/walled-garden    remove [find dynamic=no]
/ip/hotspot/walled-garden/ip remove [find dynamic=no]

/ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="HiFastLink Dashboard"
/ip/hotspot/walled-garden add dst-host=$DomainName           comment="HiFastLink Dashboard Root"
/ip/hotspot/walled-garden add dst-host="*.paystack.com"      comment="Paystack"
/ip/hotspot/walled-garden add dst-host="*.paystack.co"       comment="Paystack Alt"
/ip/hotspot/walled-garden add dst-host="*.sentry.io"         comment="Error Logs"

/ip/hotspot/walled-garden/ip add action=accept protocol=udp dst-port=53                                comment="DNS"
/ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP                                  comment="HiFastLink Server"
/ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP protocol=tcp dst-port=80         comment="HiFastLink HTTP"
/ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP protocol=tcp dst-port=443        comment="HiFastLink HTTPS"
:put ">> Walled garden ready"

# =======================================================
# STEP 11 - DNS
# Use public upstream resolvers. The router acts as a proxy
# for clients (192.168.88.1 in DHCP is correct - it points
# clients at the router which then forwards to 8.8.8.8).
# Do NOT set servers=192.168.88.1 - that is a loop.
# =======================================================
:put ">> Configuring DNS..."
/ip/dns set servers=8.8.8.8,1.1.1.1 allow-remote-requests=yes
# Wipe all static DNS entries and add only ours
/ip/dns/static remove [find dynamic=no]
:local bridgeAddrFull [/ip/address get [find interface=$BridgeName] address]
:local bridgeIP [:pick $bridgeAddrFull 0 [:find $bridgeAddrFull "/"]]
/ip/dns/static add name=$DNSName address=$bridgeIP ttl=1m comment="HiFastLink Portal"
:put (">> DNS ready - " . $DNSName . " -> " . $bridgeIP)

# =======================================================
# STEP 12 - SYSTEM: IDENTITY, SCHEDULERS, NTP, SERVICES
# =======================================================
:put ">> Configuring system..."
/system/identity set name=$LocationName

# Wipe ALL schedulers and scripts, then add only ours
/system/scheduler remove [find]
/system/script    remove [find dynamic=no]

:local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
/system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"" . $heartbeatURL . "\" mode=https output=none") comment="HiFastLink Heartbeat"

/system/script add name="realtime-stats-script" comment="HiFastLink Stats" source=":local identity [/system/identity get name]; :local apiURL \"https://hifastlink.com/api/routers/speed\"; :foreach session in=[/ip/hotspot/active find] do={:local user [/ip/hotspot/active get \$session user]; :local bytesIn [/ip/hotspot/active get \$session bytes-in]; :local bytesOut [/ip/hotspot/active get \$session bytes-out]; :local fullURL (\$apiURL . \"?identity=\" . \$identity . \"&user=\" . \$user . \"&bytes_in=\" . \$bytesIn . \"&bytes_out=\" . \$bytesOut); :do {/tool/fetch url=\$fullURL mode=https output=none} on-error={}}"
/system/scheduler add name="realtime-stats" interval=10s on-event="/system/script run realtime-stats-script" comment="HiFastLink Stats"

# NTP - wipe all servers then add ours
/system/ntp/client set enabled=yes
/system/ntp/client/servers remove [find]
/system/ntp/client/servers add address=162.159.200.1
/system/ntp/client/servers add address=162.159.200.123

# Services
/ip/service set www     disabled=no  port=80
/ip/service set www-ssl disabled=yes
/ip/service set api     disabled=no  port=8728
/ip/service set api-ssl disabled=yes
/ip/service set winbox  disabled=no  port=8291
/ip/service set telnet  disabled=yes
/ip/service set ftp     disabled=yes
/ip/service set ssh     disabled=no  port=22

:put ">> System ready"

:put "========================================"
:put ("   COMPLETE: " . $LocationName)
:put ("   Portal:   http://" . $DNSName)
:if ($VPNEnabled) do={
    :put ("   VPN IP:   " . $WGRouterIP)
    :put ("   RADIUS:   " . $WGServerIP)
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