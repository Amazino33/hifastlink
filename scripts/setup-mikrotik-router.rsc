# MikroTik Router Auto-Setup Script for HiFastLink
# Run this on each new router via: /import setup-mikrotik-router.rsc
# Or copy-paste into terminal

:log info "Starting HiFastLink router setup..."

# ============================================
# CONFIGURATION VARIABLES (EDIT THESE)
# ============================================
:local radiusServer "142.93.47.189"
:local radiusSecret "SimpleTestKey123"
:local hotspotInterface "ether2"
:local hotspotNetwork "192.168.88.0/24"
:local hotspotGateway "192.168.88.1"
:local dnsServers "8.8.8.8,8.8.4.4"
:local hotspotName "HiFastLink WiFi"
:local apiUser "hifastlink"
:local apiPassword "1a2345678B"

# ============================================
# 1. CONFIGURE RADIUS CLIENT
# ============================================
:log info "Step 1: Configuring RADIUS..."

# Remove old RADIUS servers
/radius remove [find]

# Add RADIUS server for authentication and accounting
/radius add \
    address=$radiusServer \
    secret=$radiusSecret \
    service=hotspot \
    timeout=3s \
    comment="HiFastLink RADIUS Server"

:log info "RADIUS server added: $radiusServer"

# ============================================
# 2. CONFIGURE HOTSPOT NETWORK
# ============================================
:log info "Step 2: Setting up hotspot network..."

# Configure IP address on hotspot interface
/ip address add \
    address=($hotspotGateway . "/24") \
    interface=$hotspotInterface \
    comment="HiFastLink Hotspot Gateway" \
    disabled=no

# Configure DHCP server pool
/ip pool add \
    name=hotspot-pool \
    ranges=192.168.88.10-192.168.88.254

# Setup DHCP server
/ip dhcp-server add \
    name=hotspot-dhcp \
    interface=$hotspotInterface \
    address-pool=hotspot-pool \
    disabled=no

/ip dhcp-server network add \
    address=$hotspotNetwork \
    gateway=$hotspotGateway \
    dns-server=$dnsServers \
    comment="HiFastLink Hotspot DHCP"

:log info "Network configuration complete"

# ============================================
# 3. SETUP HOTSPOT
# ============================================
:log info "Step 3: Setting up hotspot..."

# Create hotspot profile
/ip hotspot profile add \
    name=hsprof1 \
    hotspot-address=$hotspotGateway \
    dns-name="login.wifi" \
    login-by=http-chap,http-pap \
    use-radius=yes \
    radius-accounting=yes \
    radius-interim-update=1m \
    nas-port-type=wireless-802.11 \
    shared-users=10 \
    comment="HiFastLink Hotspot Profile"

:log info "Hotspot profile created with shared-users=10"

# Create hotspot server
/ip hotspot add \
    name=hotspot1 \
    interface=$hotspotInterface \
    address-pool=hotspot-pool \
    profile=hsprof1 \
    disabled=no

# Configure hotspot server profile
/ip hotspot service-port set ftp disabled=yes
/ip hotspot service-port set telnet disabled=yes
/ip hotspot service-port set imap disabled=yes
/ip hotspot service-port set pop3 disabled=yes
/ip hotspot service-port set smtp disabled=yes

:log info "Hotspot server configured"

# ============================================
# 4. CONFIGURE FIREWALL & NAT
# ============================================
:log info "Step 4: Configuring firewall..."

# Add NAT masquerade rule for hotspot traffic
/ip firewall nat add \
    chain=srcnat \
    src-address=$hotspotNetwork \
    action=masquerade \
    comment="HiFastLink Hotspot NAT"

# Allow RADIUS traffic
/ip firewall filter add \
    chain=input \
    protocol=udp \
    dst-port=1812,1813 \
    src-address=$radiusServer \
    action=accept \
    comment="Allow RADIUS from server"

:log info "Firewall rules configured"

# ============================================
# 5. ENABLE API ACCESS
# ============================================
:log info "Step 5: Enabling API access..."

# Enable API and API-SSL services
/ip service set api disabled=no port=8728
/ip service set api-ssl disabled=no port=8729

# Create API user if it doesn't exist
:if ([/user find name=$apiUser] = "") do={
    /user add \
        name=$apiUser \
        password=$apiPassword \
        group=full \
        comment="HiFastLink API User"
    :log info "API user created: $apiUser"
} else={
    /user set [find name=$apiUser] password=$apiPassword
    :log info "API user password updated: $apiUser"
}

# Restrict API access to specific IPs (optional)
# /ip service set api address=127.0.0.1,$radiusServer

:log info "API access enabled"

# ============================================
# 6. CONFIGURE DNS
# ============================================
:log info "Step 6: Configuring DNS..."

/ip dns set \
    servers=$dnsServers \
    allow-remote-requests=yes

:log info "DNS configured"

# ============================================
# 7. SET SYSTEM IDENTITY
# ============================================
:log info "Step 7: Setting system identity..."

# Prompt for router name (or set default)
:local routerName
:set routerName [/system identity get name]

:if ($routerName = "MikroTik") do={
    :log warning "Please set a unique router name!"
    :log warning "Run: /system identity set name=\"YourLocation-Hub\""
}

# ============================================
# SETUP COMPLETE
# ============================================
:log info "=========================================="
:log info "HiFastLink Router Setup Complete!"
:log info "=========================================="
:log info ""
:log info "Next Steps:"
:log info "1. Set router identity: /system identity set name=\"YourLocation-Hub\""
:log info "2. Get router's public IP: /ip address print"
:log info "3. Add router to Laravel admin panel"
:log info "4. Test with a user account"
:log info ""
:log info "Configuration Summary:"
:log info "- RADIUS Server: $radiusServer"
:log info "- Hotspot Network: $hotspotNetwork"
:log info "- Gateway: $hotspotGateway"
:log info "- DNS: $dnsServers"
:log info "- Max Devices per User: 10"
:log info "- API User: $apiUser"
:log info "- API Port: 8728"
:log info ""
:log info "=========================================="

# Display current configuration
:put ""
:put "Current Router Configuration:"
:put "============================="
/system identity print
/ip address print where interface=$hotspotInterface
/ip hotspot print
/radius print
:put ""
:put "Setup script completed successfully!"
