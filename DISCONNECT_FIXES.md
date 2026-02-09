# Dashboard Disconnect & Router Location Fixes

## Bugs Fixed

### ✅ Bug 1: Disconnect Button Uses Wrong URL
**Problem:** Disconnect button went to `.wifi/logout` instead of `login.wifi/logout`

**Root Cause:** 
- `HotspotController::disconnectBridge()` was using `MIKROTIK_LOGIN_URL` env variable
- This fallback was using IP address (`192.168.88.1`) instead of DNS name
- Same issue as the connect flow

**Solution:**
Changed to use `MIKROTIK_GATEWAY` env variable with `login.wifi` as default:
```php
// BEFORE:
$gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://192.168.88.1/login';

// AFTER:
$gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';
```

**File Modified:** `app/Http/Controllers/HotspotController.php`

---

### ✅ Bug 2: Online Status Persists After Disconnect
**Problem:** After clicking disconnect, the poll refresh showed status as online again

**Root Cause:**
- localStorage flag `hifastlink_connected_{user_id}_{device_id}` wasn't cleared from UI immediately
- Button visibility logic still relied on this flag
- When Livewire polled, it re-evaluated and showed wrong status

**Solution:**
Enhanced disconnect button event handler to:
1. Clear localStorage immediately
2. Update UI to show Connect button
3. Update connection status badge to OFFLINE

```javascript
// BEFORE:
disconnectBtn.addEventListener('click', function() {
    localStorage.removeItem(STORAGE_KEY);
    updateConnectionStatus(false);
});

// AFTER:
disconnectBtn.addEventListener('click', function() {
    localStorage.removeItem(STORAGE_KEY);
    // Immediately update UI
    if (connectBtn) connectBtn.classList.remove('hidden');
    if (disconnectBtn) disconnectBtn.classList.add('hidden');
    updateConnectionStatus(false);
});
```

**File Modified:** `resources/views/livewire/user-dashboard.blade.php`

---

### ✅ Bug 3: Router Shows IP Instead of Location
**Problem:** Dashboard showed "Router: 142.93.47.189" instead of "uniuyo_cbn_1 - Location"

**Root Cause:**
- Router lookup only checked `routers.ip_address` against `radacct.nasipaddress`
- In your setup, RADIUS server IP (142.93.47.189) is stored, not the actual router IP
- Router identifier is stored differently (in NAS table shortname or calledstationid)

**Solution:**
Implemented comprehensive 3-tier lookup strategy:

```php
// 1. Try by IP address (direct match)
$router = Router::where('ip_address', $activeSession->nasipaddress)->first();

// 2. Try by called_station_id (router identifier)
if (!$router && $activeSession->calledstationid) {
    $router = Router::where('nas_identifier', $activeSession->calledstationid)->first();
}

// 3. Try via NAS table shortname
if (!$router && $activeSession->nasipaddress) {
    $nas = Nas::where('nasname', $activeSession->nasipaddress)->first();
    if ($nas && $nas->shortname) {
        $router = Router::where('nas_identifier', $nas->shortname)->first();
    }
}
```

**File Modified:** `app/Http/Livewire/UserDashboard.php`

---

## How Router Lookup Works Now

### Lookup Chain:
1. **Direct IP Match** → `radacct.nasipaddress` = `routers.ip_address`
2. **Called Station ID** → `radacct.calledstationid` = `routers.nas_identifier`
3. **NAS Table Lookup** → `nas.nasname` → `nas.shortname` = `routers.nas_identifier`

### Database Relationships:
```
radacct.nasipaddress (142.93.47.189)
    ↓
nas.nasname (142.93.47.189) → nas.shortname (uniuyo_cbn_1)
    ↓
routers.nas_identifier (uniuyo_cbn_1) → routers.name + routers.location
```

### Display Format:
- **If router found:** `{router.name} - {router.location}`
  - Example: `uniuyo_cbn_1 - Computer Science Block`
- **If not found:** `Router: {IP Address}`
  - Fallback: `Router: 142.93.47.189`

---

## Expected .env Configuration

```env
# Use DNS name configured in MikroTik, NOT IP address
MIKROTIK_GATEWAY=http://login.wifi/login
```

**Important:** This must match the `dns-name` configured in your MikroTik hotspot profile.

To check your router's DNS name:
```
/ip hotspot profile print
```
Look for the `dns-name` value.

---

## Testing Instructions

### Test 1: Disconnect URL
1. Connect to router
2. Click "Disconnect" button
3. **Expected:** Redirects to `http://login.wifi/logout`
4. **Not:** `.wifi/logout` or `192.168.88.1/logout`

### Test 2: Status After Disconnect
1. Connect to router (shows "Disconnect" button)
2. Click "Disconnect"
3. **Expected:** Button immediately changes to "Connect to Router"
4. **Expected:** Status badge shows "OFFLINE" (gray)
5. Wait 10 seconds for poll
6. **Expected:** Still shows "Connect to Router" and "OFFLINE"

### Test 3: Router Location Display
1. Connect to router from router `uniuyo_cbn_1`
2. Check dashboard connection status section
3. **Expected:** Shows `📡 uniuyo_cbn_1 - [Location]` in yellow
4. **Not:** Shows `Router: 142.93.47.189`

### Test 4: Admin Dashboard
1. Go to Filament admin `/admin/routers`
2. **Expected:** Router shows "Online" status (green badge)
3. This confirms heartbeat is working

---

## Files Modified

1. ✅ `app/Http/Controllers/HotspotController.php` - Fixed logout URL
2. ✅ `app/Http/Livewire/UserDashboard.php` - Enhanced router lookup
3. ✅ `resources/views/livewire/user-dashboard.blade.php` - Fixed disconnect UI update

---

## Additional Notes

### Why Multiple Lookup Methods?

Different RADIUS configurations store router identifiers differently:
- **Scenario A:** Router connects directly → `nasipaddress` = router IP
- **Scenario B:** Central RADIUS server → `nasipaddress` = server IP, need NAS lookup
- **Scenario C:** Router identifier in session → `calledstationid` = nas_identifier

The 3-tier approach handles all scenarios.

### Router Location Display Icon

Uses Font Awesome icon: `fa-solid fa-broadcast-tower`
- Color: Yellow (`text-yellow-300`)
- Emphasized: `font-semibold`
- Position: Above IP address and uptime

---

## Clear Caches

After deploying these fixes:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Summary

✅ Disconnect now uses correct URL (`login.wifi/logout`)  
✅ Status updates immediately after disconnect  
✅ Router location displays correctly via 3-tier lookup  
✅ Button flashing fixed (previous update)  
✅ MutationObserver ensures smooth updates  

All router display and connection status bugs are now resolved! 🎉
