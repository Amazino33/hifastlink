#!/bin/bash

# HiFastLink Router Quick Setup Script
# This script helps you configure a new MikroTik router and register it in Laravel

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
RADIUS_SERVER="142.93.47.189"
RADIUS_SECRET="SimpleTestKey123"

echo -e "${BLUE}"
echo "============================================"
echo "  HiFastLink Router Setup Wizard"
echo "============================================"
echo -e "${NC}"

# Step 1: Gather router information
echo -e "${YELLOW}Step 1: Router Information${NC}"
echo ""

read -p "Router Name (e.g., Uyo Hub): " ROUTER_NAME
read -p "Location/Address (e.g., Leisure Complex, Uyo): " ROUTER_LOCATION
read -p "Router Public IP Address: " ROUTER_IP
read -p "NAS Identifier (e.g., router_uyo_01): " NAS_IDENTIFIER
read -p "Description (optional): " ROUTER_DESCRIPTION

echo ""
echo -e "${YELLOW}Step 2: MikroTik API Credentials${NC}"
echo ""

read -p "API Username (default: hifastlink): " API_USER
API_USER=${API_USER:-hifastlink}

read -sp "API Password: " API_PASSWORD
echo ""

read -p "API Port (default: 8728): " API_PORT
API_PORT=${API_PORT:-8728}

echo ""
echo -e "${YELLOW}Step 3: Network Configuration${NC}"
echo ""

read -p "Hotspot Interface (default: ether2): " HOTSPOT_INTERFACE
HOTSPOT_INTERFACE=${HOTSPOT_INTERFACE:-ether2}

read -p "Hotspot Network (default: 192.168.88.0/24): " HOTSPOT_NETWORK
HOTSPOT_NETWORK=${HOTSPOT_NETWORK:-192.168.88.0/24}

read -p "Hotspot Gateway (default: 192.168.88.1): " HOTSPOT_GATEWAY
HOTSPOT_GATEWAY=${HOTSPOT_GATEWAY:-192.168.88.1}

# Summary
echo ""
echo -e "${BLUE}============================================${NC}"
echo -e "${GREEN}Configuration Summary:${NC}"
echo -e "${BLUE}============================================${NC}"
echo "Router Name:       $ROUTER_NAME"
echo "Location:          $ROUTER_LOCATION"
echo "IP Address:        $ROUTER_IP"
echo "NAS Identifier:    $NAS_IDENTIFIER"
echo "API User:          $API_USER"
echo "API Port:          $API_PORT"
echo "Hotspot Interface: $HOTSPOT_INTERFACE"
echo "Hotspot Network:   $HOTSPOT_NETWORK"
echo "Hotspot Gateway:   $HOTSPOT_GATEWAY"
echo -e "${BLUE}============================================${NC}"
echo ""

read -p "Is this correct? (yes/no): " CONFIRM
if [[ "$CONFIRM" != "yes" ]]; then
    echo -e "${RED}Setup cancelled.${NC}"
    exit 1
fi

# Step 4: Generate MikroTik configuration script
echo ""
echo -e "${YELLOW}Step 4: Generating MikroTik configuration...${NC}"

CONFIG_FILE="router-config-${NAS_IDENTIFIER}.rsc"

cat > "$CONFIG_FILE" <<EOF
# HiFastLink Router Configuration
# Generated for: $ROUTER_NAME
# Date: $(date)

:log info "Starting HiFastLink router setup for $ROUTER_NAME..."

# RADIUS Configuration
/radius remove [find]
/radius add address=$RADIUS_SERVER secret=$RADIUS_SECRET service=hotspot timeout=3s comment="HiFastLink RADIUS"

# Network Configuration
/ip address add address=${HOTSPOT_GATEWAY}/24 interface=$HOTSPOT_INTERFACE comment="HiFastLink Gateway"
/ip pool add name=hotspot-pool ranges=${HOTSPOT_NETWORK%.*}.10-${HOTSPOT_NETWORK%.*}.254
/ip dhcp-server add name=hotspot-dhcp interface=$HOTSPOT_INTERFACE address-pool=hotspot-pool
/ip dhcp-server network add address=$HOTSPOT_NETWORK gateway=$HOTSPOT_GATEWAY dns-server=8.8.8.8,8.8.4.4

# Hotspot Configuration
/ip hotspot profile add name=hsprof1 hotspot-address=$HOTSPOT_GATEWAY dns-name="login.wifi" \\
    login-by=http-chap,http-pap use-radius=yes radius-accounting=yes \\
    radius-interim-update=1m nas-port-type=wireless-802.11 shared-users=10

/ip hotspot add name=hotspot1 interface=$HOTSPOT_INTERFACE address-pool=hotspot-pool profile=hsprof1

# NAT Configuration
/ip firewall nat add chain=srcnat src-address=$HOTSPOT_NETWORK action=masquerade comment="HiFastLink NAT"

# API Access
/ip service set api disabled=no port=$API_PORT
/ip service set api-ssl disabled=no port=8729

:if ([/user find name=$API_USER] = "") do={
    /user add name=$API_USER password=$API_PASSWORD group=full comment="HiFastLink API"
} else={
    /user set [find name=$API_USER] password=$API_PASSWORD
}

# Set Identity
/system identity set name="$ROUTER_NAME"

:log info "HiFastLink router setup complete!"
:put "Setup complete! Router: $ROUTER_NAME"
EOF

echo -e "${GREEN}✓ Configuration file generated: $CONFIG_FILE${NC}"

# Step 5: Register router in Laravel
echo ""
echo -e "${YELLOW}Step 5: Registering router in Laravel...${NC}"

# Generate Laravel artisan command
cat > "register-router-${NAS_IDENTIFIER}.sh" <<EOF
#!/bin/bash
# Register router in Laravel database

cd /path/to/fastlink_app

php artisan tinker --execute="
\$router = App\\Models\\Router::create([
    'name' => '$ROUTER_NAME',
    'location' => '$ROUTER_LOCATION',
    'ip_address' => '$ROUTER_IP',
    'nas_identifier' => '$NAS_IDENTIFIER',
    'secret' => '$RADIUS_SECRET',
    'api_user' => '$API_USER',
    'api_password' => '$API_PASSWORD',
    'api_port' => $API_PORT,
    'is_active' => true,
    'description' => '$ROUTER_DESCRIPTION',
]);

echo 'Router registered successfully!';
echo 'ID: ' . \$router->id;
echo 'Name: ' . \$router->name;
echo 'NAS synced to RADIUS database automatically';
"
EOF

chmod +x "register-router-${NAS_IDENTIFIER}.sh"

echo -e "${GREEN}✓ Laravel registration script generated: register-router-${NAS_IDENTIFIER}.sh${NC}"

# Step 6: Instructions
echo ""
echo -e "${BLUE}============================================${NC}"
echo -e "${GREEN}Setup Complete! Next Steps:${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""
echo -e "${YELLOW}1. Configure MikroTik Router:${NC}"
echo "   Connect to router at: ssh admin@$ROUTER_IP"
echo "   Then run: /import $CONFIG_FILE"
echo "   Or copy-paste the contents of $CONFIG_FILE"
echo ""
echo -e "${YELLOW}2. Register Router in Laravel:${NC}"
echo "   Run: ./register-router-${NAS_IDENTIFIER}.sh"
echo "   Or add via Admin Panel → Routers → New Router"
echo ""
echo -e "${YELLOW}3. Verify Setup:${NC}"
echo "   - Check /admin/routers in your Laravel admin panel"
echo "   - Test with a user account"
echo "   - Verify RADIUS authentication works"
echo ""
echo -e "${YELLOW}4. Files Generated:${NC}"
echo "   - $CONFIG_FILE (MikroTik configuration)"
echo "   - register-router-${NAS_IDENTIFIER}.sh (Laravel registration)"
echo ""
echo -e "${BLUE}============================================${NC}"
echo -e "${GREEN}Router setup wizard completed successfully!${NC}"
echo -e "${BLUE}============================================${NC}"
