# Router Login Redirection Fix

## Problem Summary
The WiFi login system was nesting `username` and `password` parameters inside the `dst` parameter, causing MikroTik routers to ignore the credentials.

## Root Causes Identified

### 1. Wrong Password Source
**Location:** `AuthenticatedSessionController::store()`  
**Issue:** Used non-existent `clear_text_password` attribute  
**Fix:** Now fetches correct RADIUS password from `radcheck` table

### 2. Incorrect URL Construction  
**Location:** `login.blade.php` bridge fallback  
**Issue:** POST method without `dst` parameter  
**Fix:** Changed to GET with all params at top level

### 3. Dead Code
**Location:** `user-dashboard.blade.php`  
**Issue:** Unreachable fallback code that could confuse developers  
**Fix:** Removed 25 lines of unreachable code

---

## Changes Made

### ✅ File 1: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Added Import:**
```php
use App\Models\RadCheck;
```

**Fixed Password Retrieval (Line ~38):**
```php
// BEFORE:
$password = $user->clear_text_password ?? $request->input('password');

// AFTER:
$rad = RadCheck::where('username', $user->username)
    ->where('attribute', 'Cleartext-Password')
    ->first();
$password = $rad ? $rad->value : ($user->radius_password ?? null);

if (!$password) {
    return redirect()->route('dashboard')
        ->withErrors(['error' => 'Missing router password. Please contact support.']);
}
```

---

### ✅ File 2: `resources/views/auth/login.blade.php`

**Fixed Bridge Fallback (Line ~184):**
```javascript
// BEFORE (Wrong - POST without dst):
const form = document.createElement('form');
form.method = 'POST';
form.action = data.redirect;
// ... only username/password fields

// AFTER (Correct - GET with all params):
const loginUrl = data.login_url || linkLogin;
const dashboardUrl = data.dashboard_url || '{{ route('dashboard') }}';

const redirectUrl = loginUrl + 
    '?username=' + encodeURIComponent(data.username) + 
    '&password=' + encodeURIComponent(data.password) + 
    '&dst=' + encodeURIComponent(dashboardUrl);

window.location.href = redirectUrl;
```

---

### ✅ File 3: `resources/views/livewire/user-dashboard.blade.php`

**Cleaned Up Dead Code (Removed Lines ~659-679):**
- Removed unreachable username/password URL construction code
- Simplified error handling
- Code now properly relies on server-built `redirect_url`

---

## Verified Working (No Changes Needed)

### ✅ `app/Http/Controllers/DashboardController.php`
Correctly builds URL using `http_build_query()`:
```php
$params = http_build_query([
    'username' => $user->username,
    'password' => $password,
    'dst' => route('dashboard'),
]);
$redirectUrl = $loginUrl . '?' . $params;
```

### ✅ `app/Http/Controllers/HotspotController.php`  
Correctly fetches RADIUS password from RadCheck

### ✅ `resources/views/hotspot/redirect_to_router.blade.php`
Already uses correct GET format:
```javascript
const target = `${base}?username=${encodeURIComponent(u)}&password=${encodeURIComponent(p)}&dst=${encodeURIComponent(d)}`;
```

---

## Expected URL Format

### ✅ CORRECT FORMAT (After Fix):
```
http://login.wifi/login?username=USER123&password=SECRET&dst=https://hifastlink.com/dashboard
```

### ❌ WRONG FORMAT (Before Fix):
```
http://login.wifi/login?dst=https://hifastlink.com/dashboard?username=USER123&password=SECRET
```

---

## Testing Instructions

1. **Clear Caches:**
   ```bash
   php artisan view:clear
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test Scenarios:**
   - ✅ Dashboard "Connect to Router" button
   - ✅ Direct `/connect-bridge` route
   - ✅ Captive portal login flow with `link-login` parameter
   - ✅ Bridge login fallback in `login.blade.php`

3. **Verify URL Structure:**
   - Open browser DevTools Network tab
   - Watch for redirect URLs
   - Confirm parameters are at top level: `?username=X&password=Y&dst=Z`

---

## MikroTik Router Configuration

Ensure your router's hotspot login page redirects to:
```
https://hifastlink.com/login?mac=$(mac)&ip=$(ip)&username=$(username)&link-login=$(link-login-only)&link-orig=$(link-orig)&error=$(error)
```

This matches the `login.html` file in your router's hotspot directory.

---

## Summary

**Files Changed:** 3  
**Files Verified:** 3  
**Lines Added:** ~25  
**Lines Removed:** ~30  
**Net Change:** Cleaner, more maintainable code

✅ All router login flows now use correct parameter structure  
✅ Credentials properly fetched from RadCheck table  
✅ MikroTik routers will correctly receive and process login credentials
