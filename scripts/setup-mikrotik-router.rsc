# ==================================================
#  HIFASTLINK ROUTER SETUP SCRIPT (v6 & v7 COMPATIBLE)
#  Author: Gem (The Developer)
# ==================================================

# --- 1. CONFIGURATION VARIABLES (EDIT HERE) ---
:global LocationName "uniuyo_cbn_1"
:global ServerIP     "142.93.47.189"
:global RadiusSecret "testing123"
:global DomainName   "hifastlink.com"
:global DNSName      "login.wifi"
:global BridgeName   "bridge"
:global WebsiteIP    "194.36.184.49"

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

# 4. Configure Hotspot Server Profile with HTTP PAP (Required for URL redirects)
:if ($isV7) do={
    /ip/hotspot/profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
} else={
    /ip hotspot profile set [find] dns-name=$DNSName html-directory=hotspot use-radius=yes login-by=http-pap,http-chap nas-port-type=wireless-802.11 radius-accounting=yes radius-interim-update=1m
}
:put (">> Hotspot DNS Name set to: " . $DNSName . " (Applied to ALL profiles)")

# 5. Configure User Profile (Limits)
:if ($isV7) do={
    /ip/hotspot/user/profile set [find] shared-users=10
} else={
    /ip hotspot user profile set [find] shared-users=10
}
:put ">> User Profile Updated (10 Devices Allowed)"

# 6. Walled Garden - DNS Based Rules
:if ($isV7) do={
    /ip/hotspot/walled-garden remove [find]
    /ip/hotspot/walled-garden add dst-host=("*." . $DomainName) comment="Allow Dashboard Subdomains"
    /ip/hotspot/walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
    /ip/hotspot/walled-garden add dst-host="*.paystack.com" comment="Allow Paystack"
    /ip/hotspot/walled-garden add dst-host="*.paystack.co" comment="Allow Paystack Alt"
    /ip/hotspot/walled-garden add dst-host="*.sentry.io" comment="Allow Error Logs"
} else={
    /ip hotspot walled-garden remove [find]
    /ip hotspot walled-garden add dst-host=("*" . $DomainName) comment="Allow Dashboard Subdomains"
    /ip hotspot walled-garden add dst-host=$DomainName comment="Allow Dashboard Root"
    /ip hotspot walled-garden add dst-host=*paystack.com comment="Allow Paystack"
    /ip hotspot walled-garden add dst-host=*paystack.co comment="Allow Paystack Alt"
    /ip hotspot walled-garden add dst-host=*sentry.io comment="Allow Error Logs"
}
:put ">> Walled Garden (DNS) Configured"

# 7. Walled Garden - IP Based Rules (CRITICAL for HTTPS)
:if ($isV7) do={
    /ip/hotspot/walled-garden/ip remove [find]
    /ip/hotspot/walled-garden/ip add action=accept dst-address=$WebsiteIP comment="HiFastLink Server IP (HTTPS)"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS Explicit"
    /ip/hotspot/walled-garden/ip add action=accept protocol=tcp dst-port=80 dst-address=$WebsiteIP comment="HTTP Explicit"
} else={
    /ip hotspot walled-garden ip remove [find]
    /ip hotspot walled-garden ip add action=accept dst-address=$WebsiteIP comment="HiFastLink Server IP (HTTPS)"
    /ip hotspot walled-garden ip add action=accept protocol=tcp dst-port=443 dst-address=$WebsiteIP comment="HTTPS Explicit"
    /ip hotspot walled-garden ip add action=accept protocol=tcp dst-port=80 dst-address=$WebsiteIP comment="HTTP Explicit"
}
:put ">> Walled Garden (IP) Configured for HTTPS"

# 8. Configure DNS for Hotspot
:if ($isV7) do={
    /ip/dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
} else={
    /ip dns set servers=8.8.8.8,8.8.4.4 allow-remote-requests=yes
}
:put ">> DNS Configured"

# 9. Heartbeat Scheduler (Router Status Monitoring)
:local heartbeatURL ("https://" . $DomainName . "/api/routers/heartbeat?identity=" . $LocationName)
:if ($isV7) do={
    /system/scheduler remove [find name="heartbeat"]
    /system/scheduler add name="heartbeat" interval=1m on-event=("/tool/fetch url=\"$heartbeatURL\" mode=https output=none")
} else={
    /system scheduler remove [find name="heartbeat"]
    /system scheduler add name="heartbeat" interval=1m on-event=("/tool fetch url=\"$heartbeatURL\" mode=https keep-result=no")
}
:put (">> Heartbeat Scheduler Added: " . $heartbeatURL)

# 10. NTP Client (Time Sync)
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

# 11. Enable API
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
:put ("   Website IP: " . $WebsiteIP)
:put ("   Heartbeat: Every 1 minute")
:put "   READY TO DEPLOY"
:put "========================================"
