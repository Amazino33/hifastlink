# Multi-Router Management System

## Overview
This system allows you to manage multiple MikroTik routers from a single admin panel, automatically syncing them with FreeRADIUS.

## Features

✅ **Centralized Router Management** - Add/edit/delete routers from Filament admin panel  
✅ **Auto-Sync with RADIUS** - Routers automatically sync to `nas` table in RADIUS database  
✅ **Location Tracking** - Users see which hub they're connected to on dashboard  
✅ **Per-Router Analytics** - View active users and bandwidth per router  
✅ **API Integration** - Store MikroTik API credentials for remote management  

## Quick Setup

### 1. Run Migration

```bash
php artisan migrate
```

### 2. Migrate Existing Router

```bash
php artisan router:migrate-default
```

This creates a router entry from your current `.env` configuration:
- IP: `MIKROTIK_API_HOST`
- API User: `MIKROTIK_API_USER`
- API Password: `MIKROTIK_API_PASSWORD`
- RADIUS Secret: `RADIUS_SECRET_KEY`

### 3. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
```

## Admin Panel Usage

### Add New Router

1. Go to **Admin Panel → Network Management → Routers**
2. Click **New Router**
3. Fill in:
   - **Name:** e.g., "Ikot Ekpene Hub"
   - **Location:** e.g., "No. 45 Oron Road, Ikot Ekpene"
   - **IP Address:** Router's public IP (e.g., "41.203.67.150")
   - **NAS Identifier:** Unique ID (e.g., "router_ikot_01")
   - **RADIUS Secret:** Shared secret for RADIUS authentication
   - **API Credentials:** (Optional) For remote MikroTik management
4. Click **Save**

**Automatic Actions:**
- ✅ Creates entry in `routers` table
- ✅ Syncs to `nas` table in RADIUS database
- ✅ FreeRADIUS immediately accepts connections from this router

### Edit Router

1. Go to **Routers** → Click router name
2. Update any field
3. Save → Automatically updates RADIUS `nas` table

### Deactivate Router

1. Edit router
2. Toggle **Active** to OFF
3. Save → Router stops accepting new connections

### Delete Router

1. Edit router → Click **Delete**
2. Confirm → Automatically removes from RADIUS `nas` table

## User Dashboard

Users see their current router location:

```
🟢 ONLINE
📱 Connected Devices: 2/2
📍 Connected via: Uyo Hub - Leisure Complex, Uyo
```

If router not in database, shows IP:
```
📍 Connected via: Router: 192.168.88.1
```

## Database Structure

### `routers` table:
```
id                  bigint
name                varchar (e.g., "Uyo Hub")
location            varchar (Address)
ip_address          varchar (Unique, used for NAS matching)
nas_identifier      varchar (Unique NAS ID)
secret              varchar (RADIUS secret)
api_user            varchar (MikroTik API username)
api_password        varchar (MikroTik API password)
api_port            int (Default: 8728)
is_active           boolean
description         text
created_at          timestamp
updated_at          timestamp
```

### RADIUS `nas` table (auto-synced):
```
id                  int
nasname             varchar (= routers.ip_address)
shortname           varchar (= routers.nas_identifier)
type                varchar (= "other")
ports               int (= 1812)
secret              varchar (= routers.secret)
description         varchar (= router name + location)
```

## How It Works

### 1. Router Created/Updated
```php
Router::create([...]) 
    → RouterObserver::created() 
    → Nas::updateOrCreate([...])
    → RADIUS nas table updated
```

### 2. User Connects
```
User → MikroTik Router (192.168.1.10)
    → RADIUS Auth Request
    → FreeRADIUS checks nas table for 192.168.1.10
    → Finds matching router with correct secret
    → Authentication succeeds
    → radacct records nasipaddress = 192.168.1.10
```

### 3. Dashboard Display
```php
RadAcct::where('username', 'user123')
    →nasipaddress = '192.168.1.10'
    
Router::where('ip_address', '192.168.1.10')->first()
    → name = "Uyo Hub"
    → location = "Leisure Complex, Uyo"
    
Dashboard shows: "Connected via: Uyo Hub - Leisure Complex, Uyo"
```

## Per-Router Analytics

### Active Users
```php
$router = Router::find(1);
echo $router->active_users_count; // Uses accessor
```

### Today's Bandwidth
```php
echo formatBytes($router->today_bandwidth);
```

### Active Sessions
```php
$sessions = $router->activeSessions; // Relationship
foreach ($sessions as $session) {
    echo $session->username;
}
```

## API Integration Example

Each router can store MikroTik API credentials:

```php
$router = Router::find(1);

// Connect to this specific router's API
$client = new \RouterOS\Client([
    'host' => $router->ip_address,
    'user' => $router->api_user,
    'pass' => $router->api_password,
    'port' => $router->api_port,
]);

// Disconnect user from specific router
$client->query('/ip/hotspot/active/remove', [
    '.id' => '*100',
]);
```

## Scaling to 100+ Routers

### Step 1: Add All Routers
Use the admin panel or bulk import via seeder:

```php
$routers = [
    ['name' => 'Uyo Hub', 'ip' => '41.203.67.150', ...],
    ['name' => 'Ikot Hub', 'ip' => '41.203.67.151', ...],
    // ... 98 more
];

foreach ($routers as $data) {
    Router::create($data); // Auto-syncs to RADIUS
}
```

### Step 2: Configure Each MikroTik
On each router, set RADIUS server:

```
/radius add address=142.93.47.189 secret=YOUR_SECRET service=hotspot
/ip hotspot profile set hsprof1 use-radius=yes radius-accounting=yes
```

### Step 3: Monitor
View all routers in admin panel:
- Active users per location
- Bandwidth usage per hub
- Online/offline status

## Security

- RADIUS secrets stored encrypted in database
- API passwords stored securely
- Only active routers (`is_active=true`) sync to RADIUS
- Deleted routers automatically removed from RADIUS

## Troubleshooting

### Router not syncing to RADIUS
```bash
# Check logs
tail -f storage/logs/laravel.log | grep "Router"

# Manual sync all routers
php artisan tinker
>>> App\Models\Router::all()->each->save();
```

### User shows wrong location
- Check if router IP in `radacct.nasipaddress` matches `routers.ip_address`
- Verify router is active: `Router::where('ip_address', '...')->first()->is_active`

### Can't delete router
- Check if router has active sessions
- Deactivate first, then delete after sessions clear

## References

- Laravel Observers: https://laravel.com/docs/eloquent#observers
- FreeRADIUS NAS table: https://wiki.freeradius.org/config/Clients
- MikroTik RADIUS: https://help.mikrotik.com/docs/display/ROS/RADIUS

---

**Ready to scale!** Add unlimited routers through the admin panel. 🚀
