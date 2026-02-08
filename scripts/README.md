# Router Setup Scripts

Automated tools for quickly setting up multiple MikroTik routers for your HiFastLink ISP system.

## 📁 Files in this Directory

### MikroTik Configuration
- **`setup-mikrotik-router.rsc`** - Complete MikroTik configuration script
  - Run directly on router via `/import`
  - Configures RADIUS, hotspot, NAT, API, etc.
  - Universal template for all routers

### Setup Wizards
- **`setup-router.sh`** - Interactive setup wizard (Linux/Mac)
- **`setup-router.bat`** - Interactive setup wizard (Windows)
  - Both scripts guide you through router setup
  - Generate custom configuration files
  - Create Laravel registration scripts

### Example Data
- **`routers-example.csv`** - Sample CSV for bulk import
  - Template for importing multiple routers
  - Modify with your router details

## 🚀 Quick Start

### Method 1: Single Router Setup (Recommended)

**On Windows:**
```cmd
cd scripts
setup-router.bat
```

**On Linux/Mac:**
```bash
cd scripts
chmod +x setup-router.sh
./setup-router.sh
```

**Follow the prompts to:**
1. Enter router details
2. Generate MikroTik configuration
3. Get Laravel registration command

### Method 2: Laravel Command (Fastest)

```bash
php artisan router:add
```

Interactive wizard that:
- Adds router to database
- Syncs to RADIUS automatically
- Generates MikroTik config file

### Method 3: Bulk Import (For 10+ Routers)

**Step 1:** Edit `routers-example.csv` with your routers:
```csv
name,location,ip_address,nas_identifier,secret
Uyo Hub,Leisure Complex,192.168.88.1,router_uyo_01,SimpleTestKey123
Ikot Hub,Oron Road,41.203.67.150,router_ikot_01,SimpleTestKey123
```

**Step 2:** Import:
```bash
php artisan router:bulk-import scripts/routers-example.csv
```

## 📝 Detailed Usage

### Using setup-mikrotik-router.rsc

**On Router (via SSH):**
```bash
# Connect to router
ssh admin@192.168.88.1

# Edit configuration variables at top of file
:local radiusServer "YOUR_RADIUS_IP"
:local radiusSecret "YOUR_SECRET"
:local hotspotInterface "YOUR_INTERFACE"

# Import script
/import setup-mikrotik-router.rsc
```

**Via Winbox:**
1. Open Winbox
2. Go to Files
3. Upload `setup-mikrotik-router.rsc`
4. Open terminal
5. Run: `/import setup-mikrotik-router.rsc`

### Using Interactive Wizards

**The wizard asks for:**
- Router name (e.g., "Uyo Hub")
- Location/address
- IP address
- NAS identifier
- API credentials
- Network configuration

**Generated outputs:**
- `router-config-{nas-id}.rsc` - MikroTik configuration
- `register-router-{nas-id}.bat/.sh` - Laravel registration

### Using Bulk Import

**CSV Format:**
```csv
name,location,ip_address,nas_identifier,secret,api_user,api_password,api_port,description,is_active
```

**Required columns:**
- `name` - Router name
- `location` - Physical location
- `ip_address` - Router IP (must be unique)
- `nas_identifier` - NAS ID (must be unique)

**Optional columns:**
- `secret` - RADIUS secret (defaults to .env value)
- `api_user` - MikroTik API username
- `api_password` - MikroTik API password
- `api_port` - API port (default: 8728)
- `description` - Additional notes
- `is_active` - true/false (default: true)

**Import command:**
```bash
php artisan router:bulk-import path/to/routers.csv
```

## 🔧 Configuration Variables

Edit these at the top of `setup-mikrotik-router.rsc`:

```routeros
:local radiusServer "142.93.47.189"     # Your RADIUS server IP
:local radiusSecret "SimpleTestKey123"   # RADIUS shared secret
:local hotspotInterface "ether2"         # Interface for hotspot
:local hotspotNetwork "192.168.88.0/24"  # Hotspot subnet
:local hotspotGateway "192.168.88.1"     # Gateway IP
:local dnsServers "8.8.8.8,8.8.4.4"      # DNS servers
:local apiUser "hifastlink"              # API username
:local apiPassword "1a2345678B"          # API password
```

## 📋 Complete Setup Workflow

> Note: Generated `.rsc` files include a Heartbeat scheduler that calls `https://{YOUR_APP}/api/routers/heartbeat?identity={NAS_ID}` every minute with `check-certificate=no`, and add Walled Garden rules for your app domain and RADIUS server so the heartbeat works even when the hotspot is locked.



### For Each New Router:

**1. Add to Laravel (Choose one):**
- Run `php artisan router:add` (interactive)
- Run setup wizard: `setup-router.bat` (Windows) or `setup-router.sh` (Linux/Mac)
- Use admin panel: /admin/routers → New Router
- Bulk import via CSV

**2. Configure MikroTik:**
- Upload and import generated `.rsc` file
- Or manually copy-paste configuration commands
- Verify with `/radius print`, `/ip hotspot profile print`

**3. Test:**
- Connect test device to hotspot
- Login with test user
- Check dashboard shows location
- Verify in admin panel: /admin/routers

**4. Monitor:**
- Admin panel shows active users per router
- Dashboard shows user's current location
- RADIUS logs show authentication requests

## 🎯 Examples

### Example 1: Add Single Router via Command
```bash
$ php artisan router:add

Router Name: Ikot Ekpene Hub
Location/Address: No. 45 Oron Road, Ikot Ekpene
Router IP Address: 41.203.67.150
NAS Identifier: router_ikot_01
RADIUS Secret: [uses .env default]
API Username: hifastlink
API Password: ********

✅ Router created successfully!
✅ NAS synced to RADIUS database
✅ Config generated: storage/app/router-configs/router-ikot_01.rsc
```

### Example 2: Bulk Import 5 Routers
```bash
$ php artisan router:bulk-import scripts/my-routers.csv

📂 Reading CSV file...
Found 5 routers to import

[Progress bar]

✅ Successfully imported: 5 routers
🎉 Bulk import complete!
```

### Example 3: Manual MikroTik Configuration
```bash
# SSH into router
ssh admin@41.203.67.150

# Upload config file (via FTP or terminal paste)
# Then import
/import router-config-ikot_01.rsc

# Verify
/radius print
/ip hotspot profile print detail
```

## 🔍 Verification Commands

### Check Router Health (Recommended)
```bash
# Comprehensive health check for specific router
php artisan router:check 1              # By ID
php artisan router:check 192.168.88.1  # By IP

# Shows:
# - Basic info (name, location, IP, status)
# - RADIUS sync status
# - Active sessions
# - Statistics (bandwidth, users)
# - API configuration
# - Health checks and recommendations
```

### Check Laravel Database
```bash
php artisan tinker

# List all routers
App\Models\Router::all()

# Find specific router
App\Models\Router::where('name', 'Ikot Hub')->first()

# Check RADIUS sync
App\Models\Nas::all()
```

### Check MikroTik
```routeros
# Identity
/system identity print

# RADIUS
/radius print detail

# Hotspot profile
/ip hotspot profile print detail

# Active users
/ip hotspot active print

# API status
/ip service print where name=api
```

## 🐛 Troubleshooting

### "Router already exists"
```bash
# Check existing routers
php artisan tinker
App\Models\Router::where('ip_address', '192.168.88.1')->first()

# Delete if needed
$router->delete()
```

### "Config file not found"
- Check path: `storage/app/router-configs/`
- Re-generate with `php artisan router:add`
- Or manually create from template

### "Import failed on MikroTik"
- Check syntax errors in .rsc file
- Verify router has space (not full)
- Import line by line to find error
- Check MikroTik logs: `/log print`

### "Router not syncing to RADIUS"
```bash
# Manually trigger sync
php artisan tinker
$router = App\Models\Router::find(1);
$router->save(); // Triggers observer
```

## 📚 Additional Resources

- **ROUTER_SETUP_GUIDE.md** - Complete setup documentation
- **MULTI_ROUTER_SETUP.md** - Architecture and features
- **MIKROTIK_SETUP_GUIDE.md** - MikroTik configuration details
- **RADIUS_USAGE_GUIDE.md** - RADIUS troubleshooting

## 🔐 Security Notes

1. **Change default secrets** - Don't use "SimpleTestKey123" in production
2. **Unique credentials per router** - Each router should have unique API password
3. **Restrict API access** - Use `/ip service set api address=...` to limit access
4. **Use strong passwords** - 16+ characters with mixed case, numbers, symbols
5. **Keep CSV files secure** - They contain credentials

## 💡 Tips

1. **Naming convention:**
   - Router: "{City} Hub"
   - NAS ID: "router_{city}_01"
   - Example: "Uyo Hub", "router_uyo_01"

2. **IP addressing:**
   - Keep router IPs documented in spreadsheet
   - Use consistent subnet ranges
   - Example: 192.168.88.1, 192.168.89.1, etc.

3. **Testing:**
   - Always test with one router first
   - Verify RADIUS authentication
   - Check dashboard location display
   - Monitor logs for errors

4. **Documentation:**
   - Keep router details in CSV (backup)
   - Document custom configurations
   - Note each location's contact info

## ⚡ Quick Commands Reference

| Task | Command |
|------|---------|
| Add single router | `php artisan router:add` |
| Bulk import | `php artisan router:bulk-import routers.csv` |
| List all routers | `php artisan tinker` → `App\Models\Router::all()` |
| Migrate default | `php artisan router:migrate-default` |
| View in admin | Visit `/admin/routers` |
| Check RADIUS sync | `App\Models\Nas::all()` |

---

**Ready to scale!** Each router takes < 5 minutes to setup. 🚀
