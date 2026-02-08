# 🚀 FastLink Multi-Router Quick Start

## 1️⃣ First Time Setup (5 minutes)

### Create Database Table
```bash
php artisan migrate
```

### Import Your Existing Router
```bash
php artisan router:migrate-default
```
This creates a router entry from your `.env` MIKROTIK_* settings.

---

## 2️⃣ Add More Routers (Choose One Method)

### Method A: Interactive CLI ⚡ (Fastest)
```bash
php artisan router:add
```
Follow the prompts. Generates MikroTik config automatically.

### Method B: Setup Wizard 📝 (Most Guided)

**Linux/Mac:**
```bash
cd scripts
bash setup-router.sh
```

**Windows:**
```cmd
cd scripts
setup-router.bat
```

### Method C: Bulk Import 🚛 (10+ Routers)
```bash
# 1. Edit routers-example.csv with your routers
# 2. Import
php artisan router:bulk-import routers-example.csv
```

---

## 3️⃣ Configure MikroTik Router

### Option 1: Use Generated Config ⭐ (Recommended)
```bash
# Config saved in: storage/app/router-configs/router-config-{nas-id}.rsc
# Upload to MikroTik via FTP or copy-paste
# Then on router:
/import router-config-{nas-id}.rsc
```

### Option 2: Manual Configuration
See [scripts/setup-mikrotik-router.rsc](scripts/setup-mikrotik-router.rsc) for reference.

---

## 4️⃣ Verify Setup

### Check Router Health
```bash
php artisan router:check 1              # By ID
php artisan router:check 192.168.88.1  # By IP
```

### Check RADIUS Sync
```bash
php artisan tinker
>>> App\Models\Nas::all()
```

### Check Admin Panel
Visit: `/admin/routers`

---

## 🔥 Common Commands

| Command | Purpose |
|---------|---------|
| `php artisan router:add` | Add single router interactively |
| `php artisan router:bulk-import file.csv` | Import multiple routers from CSV |
| `php artisan router:check {id}` | Health check for specific router |
| `php artisan router:migrate-default` | Import existing router from .env |

---

## 📊 Router Dashboard

Users see their connection location automatically:

**User Dashboard Shows:**
- ✅ Connected Devices: 2/5
- ✅ Location: "Connected via: Ikot Ekpene Hub - Zone A"
- ✅ Bandwidth usage
- ✅ Remaining data

**How It Works:**
1. User connects to any router
2. RADIUS records `nasipaddress` in `radacct` table
3. Dashboard matches IP to router
4. Displays router name + location

---

## 🛠️ Troubleshooting

### Router Not Syncing to RADIUS?
```bash
php artisan tinker
>>> $router = App\Models\Router::find(1)
>>> $router->save()  # Triggers observer to sync
```

### Location Not Showing?
1. Check user is connected: `php artisan router:check {id}`
2. Verify IP match: Router IP must match `radacct.nasipaddress`
3. Check connection: User must have active session

### Need to Update Router?
```bash
# Via admin panel: /admin/routers/{id}/edit
# Or via tinker:
$router = App\Models\Router::find(1)
$router->name = 'New Name'
$router->save()  # Auto-syncs to RADIUS
```

### Device Limits Not Working? ⚠️
If users can connect more devices than allowed (e.g., showing 3/2):

```bash
# Check violations
php check_simultaneous_use.php

# Clean stale sessions and enforce limits
php artisan radius:enforce-limits --clean-stale --disconnect
```

**Root Cause**: FreeRADIUS session checking not configured.  
**Fix**: Follow [FREERADIUS_SIMULTANEOUS_USE.md](FREERADIUS_SIMULTANEOUS_USE.md) to enable session enforcement.

---

## 📖 Full Documentation

- **[Multi-Router Setup Guide](MULTI_ROUTER_SETUP.md)** - System architecture
- **[Router Setup Guide](ROUTER_SETUP_GUIDE.md)** - Step-by-step deployment
- **[Scripts README](scripts/README.md)** - All automation methods
- **[MikroTik API Setup](MIKROTIK_API_SETUP.md)** - Auto-disconnect feature

---

## 🎯 Production Checklist

Before going live with multiple routers:

- [ ] Run `php artisan migrate` on production
- [ ] Import all routers (bulk CSV recommended)
- [ ] Configure each MikroTik with generated .rsc file
- [ ] Verify RADIUS sync: `php artisan router:check {id}`
- [ ] Test user connection on each router
- [ ] Confirm location showing on dashboard
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Run `php artisan config:cache`
- [ ] Monitor logs: `tail -f storage/logs/laravel.log`

---

## 💡 Pro Tips

1. **Bulk Import Template**: Use [scripts/routers-example.csv](scripts/routers-example.csv) as template
2. **Auto-Configuration**: `php artisan router:add` generates MikroTik config automatically
3. **Health Monitoring**: Run `php artisan router:check {id}` regularly
4. **RADIUS Auto-Sync**: All router changes sync to NAS table automatically via observer
5. **Location Tracking**: Works automatically - no extra configuration needed

---

## 🆘 Need Help?

1. Check router health: `php artisan router:check {id}`
2. Review logs: `storage/logs/laravel.log`
3. Test RADIUS: `php artisan diagnose:radius {username}`
4. Check documentation: `ROUTER_SETUP_GUIDE.md`

---

## 📞 Quick Reference

**Add Router:** `php artisan router:add`  
**Check Router:** `php artisan router:check {id}`  
**Bulk Import:** `php artisan router:bulk-import file.csv`  
**Admin Panel:** `/admin/routers`  
**Config Location:** `storage/app/router-configs/`

---

**That's it!** Your multi-router ISP is ready to scale. 🚀
