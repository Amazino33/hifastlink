# FreeRADIUS Simultaneous-Use Configuration

## ⚠️ Problem
RADIUS/MikroTik is NOT enforcing the `Simultaneous-Use` attribute. Users can connect more devices than allowed (e.g., 3/2 devices connected).

## ✅ Solution
Enable FreeRADIUS session checking to enforce concurrent connection limits.

---

## Step 1: Enable Session Module

Edit `/etc/freeradius/3.0/sites-enabled/default`:

### In the `authorize` section:
```
authorize {
    preprocess
    chap
    mschap
    digest
    suffix
    eap
    files
    
    # Check for multiple logins
    sql
    
    # THIS IS CRITICAL - Add this line:
    -sql_session_check
    
    expiration
    logintime
    pap
}
```

### In the `session` section:
```
session {
    # THIS IS CRITICAL - Add this:
    sql
}
```

### In the `post-auth` section:
```
post-auth {
    # THIS IS CRITICAL - Add this:
    sql
    
    exec
    remove_reply_message_if_eap
    
    Post-Auth-Type REJECT {
        attr_filter.access_reject
        eap
        remove_reply_message_if_eap
    }
}
```

---

## Step 2: Configure SQL Module for Session Checking

Edit `/etc/freeradius/3.0/mods-enabled/sql`:

```sql
sql {
    driver = "rlm_sql_mysql"
    dialect = "mysql"
    
    server = "localhost"  # Or your RADIUS DB IP
    port = 3306
    login = "admin"
    password = "your_radius_password"
    
    radius_db = "hifastlink"
    
    # Session checking queries
    session_state_query = "\
        SELECT \
            radacctid, \
            acctsessionid, \
            username, \
            nasipaddress, \
            nasportid, \
            framedipaddress, \
            callingstationid, \
            framedprotocol, \
            acctstarttime, \
            acctupdatetime, \
            acctstoptime, \
            acctinputoctets, \
            acctoutputoctets \
        FROM ${acct_table1} \
        WHERE username = '%{SQL-User-Name}' \
        AND acctstoptime IS NULL"
    
    # Update session on start
    accounting_start_query = "\
        INSERT INTO ${acct_table1} \
            (acctsessionid, acctuniqueid, username, \
             realm, nasipaddress, nasportid, \
             nasporttype, acctstarttime, acctupdatetime, \
             acctstoptime, acctsessiontime, acctauthentic, \
             connectinfo_start, connectinfo_stop, \
             acctinputoctets, acctoutputoctets, \
             calledstationid, callingstationid, \
             acctterminatecause, servicetype, framedprotocol, \
             framedipaddress) \
        VALUES \
            ('%{Acct-Session-Id}', '%{Acct-Unique-Session-Id}', \
             '%{SQL-User-Name}', '%{Realm}', '%{NAS-IP-Address}', \
             '%{%{NAS-Port-ID}:-%{NAS-Port}}', '%{NAS-Port-Type}', \
             FROM_UNIXTIME(%{integer:Event-Timestamp}), \
             FROM_UNIXTIME(%{integer:Event-Timestamp}), \
             NULL, '0', '%{Acct-Authentic}', '%{Connect-Info}', \
             '', '0', '0', '%{Called-Station-Id}', \
             '%{Calling-Station-Id}', '', '%{Service-Type}', \
             '%{Framed-Protocol}', '%{Framed-IP-Address}')"
    
    # Read clients from database (optional)
    read_clients = yes
    client_table = "nas"
}
```

---

## Step 3: Enable Simultaneous-Use Checking

Create `/etc/freeradius/3.0/mods-enabled/sql_session_check`:

```
# Check for simultaneous logins
sql sql_session_check {
    # Use main SQL module configuration
    driver = "${modules.sql.driver}"
    
    sql_user_name = "%{User-Name}"
    
    # Query to count active sessions
    session_check_query = "\
        SELECT COUNT(*) \
        FROM ${acct_table1} \
        WHERE username = '%{SQL-User-Name}' \
        AND acctstoptime IS NULL"
}
```

---

## Step 4: Configure checkrad Script

FreeRADIUS uses `checkrad` to verify if a session is truly active.

Edit `/etc/freeradius/3.0/mods-config/sql/main/mysql/queries.conf`:

```sql
# Verify active session
session_check_query = "\
    SELECT \
        COUNT(*) \
    FROM ${acct_table1} \
    WHERE username = '%{SQL-User-Name}' \
    AND acctstoptime IS NULL"

# Get session details for verification
session_detail_query = "\
    SELECT \
        radacctid, \
        acctsessionid, \
        username, \
        nasipaddress, \
        nasportid, \
        framedipaddress, \
        callingstationid \
    FROM ${acct_table1} \
    WHERE username = '%{SQL-User-Name}' \
    AND acctstoptime IS NULL"
```

---

## Step 5: Restart FreeRADIUS

```bash
# Check configuration syntax
sudo freeradius -CX

# If no errors, restart
sudo systemctl restart freeradius

# Monitor logs
sudo tail -f /var/log/freeradius/radius.log
```

---

## Step 6: Verify Configuration

### Test 1: Check Simultaneous-Use in Database
```bash
mysql -u admin -p hifastlink

SELECT username, attribute, value 
FROM radcheck 
WHERE attribute = 'Simultaneous-Use';

# Should show:
# +----------+------------------+-------+
# | username | attribute        | value |
# +----------+------------------+-------+
# | testuser | Simultaneous-Use | 2     |
# +----------+------------------+-------+
```

### Test 2: Check Active Sessions
```bash
mysql -u admin -p hifastlink

SELECT username, COUNT(*) as sessions
FROM radacct
WHERE acctstoptime IS NULL
GROUP BY username;
```

### Test 3: Test Connection Limit
1. Connect device 1 with username/password → Should succeed
2. Connect device 2 with same credentials → Should succeed
3. Connect device 3 with same credentials → **Should FAIL with Access-Reject**

### Test 4: Monitor RADIUS Logs
```bash
sudo tail -f /var/log/freeradius/radius.log

# When 3rd device tries to connect, you should see:
# (X) Simultaneous-Use check failed
# (X) sql_session_check: User testuser rejected, too many sessions (3 > 2)
# (X) Access-Reject
```

---

## Step 7: Verify from Laravel

```bash
php artisan tinker

# Check user's limit
$user = App\Models\User::where('username', 'testuser')->first();
$limit = App\Models\RadCheck::where('username', $user->username)
    ->where('attribute', 'Simultaneous-Use')
    ->first();
echo "Limit: " . $limit->value;

# Check active sessions
$active = App\Models\RadAcct::where('username', $user->username)
    ->whereNull('acctstoptime')
    ->count();
echo "Active: " . $active;

# Should show: Active <= Limit
```

---

## Troubleshooting

### Issue 1: Still Allowing Too Many Connections

**Check if session module is actually running:**
```bash
sudo freeradius -X

# Look for:
# Loading module "sql"
# Loading module "sql_session_check"
# Instantiating session {
#   sql
# }
```

**Check radacct cleanup:**
```sql
-- Find stale sessions (connected > 24 hours without updates)
SELECT username, acctsessionid, acctstarttime, acctupdatetime
FROM radacct
WHERE acctstoptime IS NULL
AND acctupdatetime < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Clean them up
UPDATE radacct
SET acctstoptime = acctupdatetime,
    acctterminatecause = 'Session-Timeout'
WHERE acctstoptime IS NULL
AND acctupdatetime < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### Issue 2: False Rejections

**MikroTik not sending Accounting-Stop:**

Check MikroTik accounting:
```
/radius print
# Verify accounting=yes

/ip hotspot profile print detail
# Verify accounting is enabled
```

**Add to MikroTik:**
```
/radius
set 0 timeout=5s accounting=yes
```

### Issue 3: Sessions Not Closing

**Add cron job to clean stale sessions:**

Create `/etc/cron.hourly/radius-cleanup`:
```bash
#!/bin/bash
mysql -u admin -p'your_password' hifastlink <<EOF
UPDATE radacct
SET acctstoptime = acctupdatetime,
    acctterminatecause = 'Lost-Carrier'
WHERE acctstoptime IS NULL
AND acctupdatetime < DATE_SUB(NOW(), INTERVAL 4 HOUR);
EOF
```

```bash
chmod +x /etc/cron.hourly/radius-cleanup
```

---

## Quick Fix (Temporary)

If you can't update FreeRADIUS config immediately, add this Laravel middleware:

Create `app/Http/Middleware/EnforceDeviceLimit.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RadAcct;
use App\Models\RadCheck;

class EnforceDeviceLimit
{
    public function handle(Request $request, Closure $next)
    {
        $username = $request->input('username');
        
        if (!$username) {
            return $next($request);
        }
        
        // Get user's limit
        $limit = RadCheck::where('username', $username)
            ->where('attribute', 'Simultaneous-Use')
            ->value('value');
        
        if (!$limit) {
            return $next($request);
        }
        
        // Count active sessions
        $activeSessions = RadAcct::where('username', $username)
            ->whereNull('acctstoptime')
            ->count();
        
        // Reject if limit exceeded
        if ($activeSessions >= $limit) {
            return response()->json([
                'error' => 'Device limit reached',
                'active' => $activeSessions,
                'limit' => $limit
            ], 403);
        }
        
        return $next($request);
    }
}
```

Add to `routes/web.php`:
```php
Route::post('/hotspot/login', [HotspotController::class, 'login'])
    ->middleware('enforce.device.limit');
```

**⚠️ This is NOT recommended** - RADIUS should enforce this, not Laravel.

---

## Production Checklist

- [ ] FreeRADIUS session module enabled in `authorize` section
- [ ] SQL module configured with `session_check_query`
- [ ] `sql_session_check` module created and enabled
- [ ] Session tracking enabled in `session` section
- [ ] Post-auth SQL logging enabled
- [ ] FreeRADIUS restarted without errors
- [ ] Test: 3rd device connection rejected
- [ ] MikroTik accounting enabled and working
- [ ] Stale session cleanup cron job added
- [ ] Monitor logs for 24 hours
- [ ] All users have Simultaneous-Use in radcheck

---

## Verification Script

Create `check_simultaneous_use.php` in project root:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadAcct;

echo "🔍 Checking Simultaneous-Use Enforcement\n\n";

$users = User::whereNotNull('username')->get();

foreach ($users as $user) {
    $limit = RadCheck::where('username', $user->username)
        ->where('attribute', 'Simultaneous-Use')
        ->value('value') ?? 'NONE';
    
    $active = RadAcct::where('username', $user->username)
        ->whereNull('acctstoptime')
        ->count();
    
    $status = $active > $limit ? '❌ OVER LIMIT' : '✅ OK';
    
    echo sprintf(
        "%-20s | Limit: %-4s | Active: %-4s | %s\n",
        $user->username,
        $limit,
        $active,
        $status
    );
}

echo "\n";
```

Run: `php check_simultaneous_use.php`

---

## Summary

The fix requires **FreeRADIUS configuration**, not Laravel changes:

1. **Enable session checking** in FreeRADIUS sites-enabled/default
2. **Configure SQL module** with session queries
3. **Enable accounting** on MikroTik
4. **Add cleanup cron** for stale sessions
5. **Test enforcement** with 3 devices

After these changes, RADIUS will reject connections exceeding Simultaneous-Use limit.
