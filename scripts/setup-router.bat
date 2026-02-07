@echo off
REM HiFastLink Router Quick Setup Script (Windows Version)
REM This script helps you configure a new MikroTik router and register it in Laravel

setlocal enabledelayedexpansion

echo ============================================
echo   HiFastLink Router Setup Wizard
echo ============================================
echo.

REM Configuration
set RADIUS_SERVER=142.93.47.189
set RADIUS_SECRET=SimpleTestKey123

REM Step 1: Gather router information
echo Step 1: Router Information
echo.

set /p ROUTER_NAME="Router Name (e.g., Uyo Hub): "
set /p ROUTER_LOCATION="Location/Address (e.g., Leisure Complex, Uyo): "
set /p ROUTER_IP="Router Public IP Address: "
set /p NAS_IDENTIFIER="NAS Identifier (e.g., router_uyo_01): "
set /p ROUTER_DESCRIPTION="Description (optional): "

echo.
echo Step 2: MikroTik API Credentials
echo.

set /p API_USER="API Username (default: hifastlink): "
if "%API_USER%"=="" set API_USER=hifastlink

set /p API_PASSWORD="API Password: "

set /p API_PORT="API Port (default: 8728): "
if "%API_PORT%"=="" set API_PORT=8728

echo.
echo Step 3: Network Configuration
echo.

set /p HOTSPOT_INTERFACE="Hotspot Interface (default: ether2): "
if "%HOTSPOT_INTERFACE%"=="" set HOTSPOT_INTERFACE=ether2

set /p HOTSPOT_NETWORK="Hotspot Network (default: 192.168.88.0/24): "
if "%HOTSPOT_NETWORK%"=="" set HOTSPOT_NETWORK=192.168.88.0/24

set /p HOTSPOT_GATEWAY="Hotspot Gateway (default: 192.168.88.1): "
if "%HOTSPOT_GATEWAY%"=="" set HOTSPOT_GATEWAY=192.168.88.1

REM Summary
echo.
echo ============================================
echo Configuration Summary:
echo ============================================
echo Router Name:       %ROUTER_NAME%
echo Location:          %ROUTER_LOCATION%
echo IP Address:        %ROUTER_IP%
echo NAS Identifier:    %NAS_IDENTIFIER%
echo API User:          %API_USER%
echo API Port:          %API_PORT%
echo Hotspot Interface: %HOTSPOT_INTERFACE%
echo Hotspot Network:   %HOTSPOT_NETWORK%
echo Hotspot Gateway:   %HOTSPOT_GATEWAY%
echo ============================================
echo.

set /p CONFIRM="Is this correct? (yes/no): "
if /i not "%CONFIRM%"=="yes" (
    echo Setup cancelled.
    exit /b 1
)

REM Step 4: Generate MikroTik configuration script
echo.
echo Step 4: Generating MikroTik configuration...

set CONFIG_FILE=router-config-%NAS_IDENTIFIER%.rsc

(
echo # HiFastLink Router Configuration
echo # Generated for: %ROUTER_NAME%
echo # Date: %date% %time%
echo.
echo :log info "Starting HiFastLink router setup for %ROUTER_NAME%..."
echo.
echo # RADIUS Configuration
echo /radius remove [find]
echo /radius add address=%RADIUS_SERVER% secret=%RADIUS_SECRET% service=hotspot timeout=3s comment="HiFastLink RADIUS"
echo.
echo # Network Configuration
echo /ip address add address=%HOTSPOT_GATEWAY%/24 interface=%HOTSPOT_INTERFACE% comment="HiFastLink Gateway"
echo /ip pool add name=hotspot-pool ranges=%HOTSPOT_NETWORK:~0,-5%.10-%HOTSPOT_NETWORK:~0,-5%.254
echo /ip dhcp-server add name=hotspot-dhcp interface=%HOTSPOT_INTERFACE% address-pool=hotspot-pool
echo /ip dhcp-server network add address=%HOTSPOT_NETWORK% gateway=%HOTSPOT_GATEWAY% dns-server=8.8.8.8,8.8.4.4
echo.
echo # Hotspot Configuration
echo /ip hotspot profile add name=hsprof1 hotspot-address=%HOTSPOT_GATEWAY% dns-name="login.wifi" \
echo     login-by=http-chap,http-pap use-radius=yes radius-accounting=yes \
echo     radius-interim-update=1m nas-port-type=wireless-802.11 shared-users=10
echo.
echo /ip hotspot add name=hotspot1 interface=%HOTSPOT_INTERFACE% address-pool=hotspot-pool profile=hsprof1
echo.
echo # NAT Configuration
echo /ip firewall nat add chain=srcnat src-address=%HOTSPOT_NETWORK% action=masquerade comment="HiFastLink NAT"
echo.
echo # API Access
echo /ip service set api disabled=no port=%API_PORT%
echo /ip service set api-ssl disabled=no port=8729
echo.
echo :if ([/user find name=%API_USER%] = ""^) do={
echo     /user add name=%API_USER% password=%API_PASSWORD% group=full comment="HiFastLink API"
echo } else={
echo     /user set [find name=%API_USER%] password=%API_PASSWORD%
echo }
echo.
echo # Set Identity
echo /system identity set name="%ROUTER_NAME%"
echo.
echo :log info "HiFastLink router setup complete!"
echo :put "Setup complete! Router: %ROUTER_NAME%"
) > "%CONFIG_FILE%"

echo [92m✓ Configuration file generated: %CONFIG_FILE%[0m

REM Step 5: Generate Laravel registration command
echo.
echo Step 5: Generating Laravel registration script...

set REGISTER_SCRIPT=register-router-%NAS_IDENTIFIER%.bat

(
echo @echo off
echo REM Register router in Laravel database
echo.
echo cd /d "%%~dp0.."
echo.
echo php artisan tinker --execute="$router = App\Models\Router::create(['name' =^> '%ROUTER_NAME%', 'location' =^> '%ROUTER_LOCATION%', 'ip_address' =^> '%ROUTER_IP%', 'nas_identifier' =^> '%NAS_IDENTIFIER%', 'secret' =^> '%RADIUS_SECRET%', 'api_user' =^> '%API_USER%', 'api_password' =^> '%API_PASSWORD%', 'api_port' =^> %API_PORT%, 'is_active' =^> true, 'description' =^> '%ROUTER_DESCRIPTION%']); echo 'Router registered successfully!'; echo 'ID: ' . $router-^>id; echo 'Name: ' . $router-^>name; echo 'NAS synced to RADIUS database automatically';"
echo.
echo pause
) > "%REGISTER_SCRIPT%"

echo [92m✓ Laravel registration script generated:%REGISTER_SCRIPT%[0m

REM Step 6: Instructions
echo.
echo ============================================
echo Setup Complete! Next Steps:
echo ============================================
echo.
echo [93m1. Configure MikroTik Router:[0m
echo    Connect to router: ssh admin@%ROUTER_IP%
echo    Then run: /import %CONFIG_FILE%
echo    Or copy-paste the contents of %CONFIG_FILE%
echo.
echo [93m2. Register Router in Laravel:[0m
echo    Run: %REGISTER_SCRIPT%
echo    Or add via Admin Panel -^> Routers -^> New Router
echo.
echo [93m3. Verify Setup:[0m
echo    - Check /admin/routers in your Laravel admin panel
echo    - Test with a user account
echo    - Verify RADIUS authentication works
echo.
echo [93m4. Files Generated:[0m
echo    - %CONFIG_FILE% (MikroTik configuration)
echo    - %REGISTER_SCRIPT% (Laravel registration)
echo.
echo ============================================
echo [92mRouter setup wizard completed successfully![0m
echo ============================================
echo.
pause
