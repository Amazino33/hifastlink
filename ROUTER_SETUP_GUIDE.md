# Quick Router Setup Guide

## Overview
This guide shows you how to quickly set up new MikroTik routers for your multi-site HiFastLink system.

## Three Methods to Add Routers

### Method 1: Interactive Laravel Command (Recommended)

```bash
php artisan router:add
```

**Interactive Wizard asks for:**
- Router Name (e.g., "Ikot Ekpene Hub")
- Location (e.g., "No. 45 Oron Road, Ikot Ekpene")
- IP Address
- NAS Identifier
- RADIUS Secret
- API Credentials

**Automatically:**
- ✅ Creates router in database
- ✅ Syncs to RADIUS NAS table
- ✅ Generates MikroTik configuration script
- ✅ Ready to use immediately

**Example:**
```bash
$ php artisan router:add

🚀 HiFastLink Router Registration Wizard

 Router Name (e.g., "Uyo Hub"):
 > Ikot Ekpene Hub

 Location/Address:
 > No. 45 Oron Road, Ikot Ekpene

 Router IP Address:
 > 41.203.67.150

 NAS Identifier [router_ikot_ekpene_hub]:
 > router_ikot_01

 RADIUS Secret [SimpleTestKey123]:
 > 

 MikroTik API Username (optional) [admin]:
 > hifastlink

 MikroTik API Password (optional):
 > 

✅ Router created successfully!
✅ Router automatically synced to RADIUS NAS table
✅ MikroTik configuration file generated!

🎉 Router setup complete!
```

---

### Method 2: Bash Setup Script (For Linux/Mac)

```bash
cd scripts
chmod +x setup-router.sh
./setup-router.sh
```

**Interactive Script:**
- Asks for all router details
- Generates MikroTik configuration (.rsc file)
- Generates Laravel registration script
- Provides step-by-step instructions

**Generated Files:**
- `router-config-{nas-id}.rsc` - MikroTik configuration
- `register-router-{nas-id}.sh` - Laravel registration command

---

### Method 3: Admin Panel (GUI)

1. Go to **Admin Panel → Network Management → Routers**
2. Click **New Router**
3. Fill in the form:
   - **Router Information:** Name, Location, Description
   - **Network Configuration:** IP, NAS ID, RADIUS Secret
   - **MikroTik API:** Username, Password, Port
4. Click **Save**
5. Done! Router automatically syncs to RADIUS

---

## Complete Setup Process

### Step 1: Prepare Router Information

Before starting, gather:
- [ ] Router name (e.g., "Ikot Ekpene Hub")
- [ ] Physical location/address
- [ ] Router's public IP address
- [ ] Unique NAS identifier (e.g., "router_ikot_01")
- [ ] RADIUS shared secret (default: SimpleTestKey123)
- [ ] MikroTik admin credentials

### Step 2: Add Router to Laravel

**Option A: Command Line**
```bash
php artisan router:add
```

**Option B: Admin Panel**
- Navigate to /admin/routers
- Click "New Router"
- Fill form and save

**Option C: Programmatically**
```bash
php artisan tinker

$router = App\Models\Router::create([
    'name' => 'Ikot Ekpene Hub',
    'location' => 'No. 45 Oron Road, Ikot Ekpene',
    'ip_address' => '41.203.67.150',
    'nas_identifier' => 'router_ikot_01',
    'secret' => 'SimpleTestKey123',
    'api_user' => 'hifastlink',
    'api_password' => '1a2345678B',
    'api_port' => 8728,
    'is_active' => true,
]);
```

### Step 3: Configure MikroTik Router

**Option A: Use Generated Script**

If you used `php artisan router:add`, a config file was generated:
```bash
# Location: storage/app/router-configs/router-{nas-id}.rsc

# Connect to router
ssh admin@{ROUTER_IP}

# Import config (after uploading via FTP)
/import router-{nas-id}.rsc
```

**Option B: Manual Configuration**

Connect to router via Winbox or SSH:

```routeros
# 1. RADIUS Configuration
/radius add \
    address=142.93.47.189 \
    secret=SimpleTestKey123 \
    service=hotspot \
    timeout=3s

# 2. Hotspot Profile
/ip hotspot profile set [find name=hsprof1] \
    use-radius=yes \
    radius-accounting=yes \
    radius-interim-update=1m \
    shared-users=10

# 3. Enable API
/ip service set api disabled=no port=8728
/ip service set api-ssl disabled=no

# 4. Create API User
/user add \
    name=hifastlink \
    password=1a2345678B \
    group=full

# 5. Set Identity
/system identity set name="Ikot Ekpene Hub"

# 6. NAT (if not already configured)
/ip firewall nat add \
    chain=srcnat \
    src-address=192.168.88.0/24 \
    action=masquerade
```

**Option C: Use Pre-made Script**

Upload `scripts/setup-mikrotik-router.rsc` to router, then:
```routeros
/import setup-mikrotik-router.rsc
```

### Step 4: Verify Setup

#### Check Laravel Database
```bash
php artisan tinker

# View router
App\Models\Router::where('name', 'Ikot Ekpene Hub')->first()

# Check if synced to RADIUS
App\Models\Nas::where('nasname', '41.203.67.150')->first()
```

#### Check MikroTik
```routeros
# Check RADIUS
/radius print

# Check Hotspot Profile
/ip hotspot profile print detail

# Check Identity
/system identity print

# Check API Status
/ip service print where name=api

# Check Active Users
/ip hotspot active print
```

#### Test Connection
1. Connect a test device to the hotspot
2. Try to browse (should redirect to login page)
3. Login with a test user account
4. Check dashboard - should show location:
   ```
   📍 Connected via: Ikot Ekpene Hub - No. 45 Oron Road, Ikot Ekpene
   ```

### Step 5: Monitor

#### View in Admin Panel
- Go to /admin/routers
- See all routers with:
  - Active users count
  - Status (active/inactive)
  - Location details

#### Check RADIUS Logs
```bash
tail -f /var/log/freeradius/radius.log | grep "Ikot Ekpene Hub"
```

#### Check MikroTik Logs
```routeros
/log print where topics~"hotspot|radius"
```

---

## Bulk Router Setup

### Setup 10 Routers Quickly

Create a CSV file `routers.csv`:
```csv
name,location,ip_address,nas_identifier
Uyo Hub,Leisure Complex Uyo,41.203.67.150,router_uyo_01
Ikot Hub,Oron Road Ikot,41.203.67.151,router_ikot_01
Eket Hub,Main Street Eket,41.203.67.152,router_eket_01
```

Import via Laravel command:
```bash
php artisan router:bulk-import routers.csv
```

Or via tinker:
```php
foreach($routers as $data) {
    App\Models\Router::create([
        'name' => $data['name'],
        'location' => $data['location'],
        'ip_address' => $data['ip_address'],
        'nas_identifier' => $data['nas_identifier'],
        'secret' => 'SimpleTestKey123',
        'is_active' => true,
    ]);
}
```

---

## Troubleshooting

### Router Not Appearing in Admin Panel
```bash
# Check database
php artisan tinker
App\Models\Router::all()

# Clear cache
php artisan cache:clear
```

### Router Not Syncing to RADIUS
```bash
# Check RADIUS NAS table
php artisan tinker
App\Models\Nas::all()

# Manually trigger sync
$router = App\Models\Router::find(1);
$router->save(); // Triggers observer
```

### Users Can't Connect
1. **Check RADIUS secret matches:**
   ```routeros
   /radius print detail
   ```
   
2. **Check hotspot profile uses RADIUS:**
   ```routeros
   /ip hotspot profile print detail
   ```

3. **Test RADIUS from MikroTik:**
   ```routeros
   /radius monitor 0
   ```

4. **Check firewall:**
   ```routeros
   /ip firewall filter print
   ```

### Location Not Showing on Dashboard
1. Check if router IP matches `radacct.nasipaddress`
2. Verify router is active: `is_active=true`
3. Clear browser cache and refresh dashboard

---

## Quick Reference Card

### Add Router (CLI)
```bash
php artisan router:add
```

### View All Routers
```bash
php artisan tinker
App\Models\Router::all()
```

### Get Router by IP
```bash
App\Models\Router::where('ip_address', '41.203.67.150')->first()
```

### Check Active Users on Router
```bash
$router = App\Models\Router::find(1);
echo $router->active_users_count;
```

### Disable Router
```bash
$router = App\Models\Router::find(1);
$router->is_active = false;
$router->save();
```

### Delete Router
```bash
$router = App\Models\Router::find(1);
$router->delete(); // Auto-removes from RADIUS
```

---

## Best Practices

1. **Naming Convention:**
   - Name: "{City} Hub" (e.g., "Uyo Hub")
   - NAS ID: "router_{city}_01" (e.g., "router_uyo_01")

2. **Secrets:**
   - Use strong, unique secrets per router
   - Store in password manager
   - Don't use default "testing123" in production

3. **API Access:**
   - Always create dedicated API user
   - Don't use admin account
   - Restrict API to specific IPs if possible

4. **Testing:**
   - Test with one user first
   - Verify location displays correctly
   - Check RADIUS logs for errors

5. **Documentation:**
   - Keep router details in spreadsheet
   - Document any custom configurations
   - Note location contact person/phone

---

## Support

### Check Logs
- Laravel: `storage/logs/laravel.log`
- RADIUS: `/var/log/freeradius/radius.log`
- MikroTik: `/log print`

### Get Help
- Check MULTI_ROUTER_SETUP.md for detailed info
- Check RADIUS_USAGE_GUIDE.md for RADIUS troubleshooting
- Check MIKROTIK_SETUP_GUIDE.md for router config

---

**Ready to scale!** Each new router takes less than 5 minutes to setup. 🚀
