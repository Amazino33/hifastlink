# MikroTik RADIUS Accounting Configuration Guide

## Step 1: Access Router
1. Open Winbox
2. Connect to your MikroTik router
3. Go to Terminal window

## Step 2: Configure RADIUS Server
```
/radius
add service=hotspot address=142.93.47.189 secret=YourSharedSecret123
add service=ppp address=142.93.47.189 secret=YourSharedSecret123
```

**Explanation:**
- `service=hotspot`: For hotspot users
- `service=ppp`: For PPP/PPTP/L2TP users
- `address`: Your Ubuntu server IP
- `secret`: Must match FreeRADIUS client secret

## Step 3: Configure Hotspot Profile
```
/ip hotspot profile
set [ find default=yes ] \
    login-by=http-chap,http-pap,trial \
    use-radius=yes \
    radius-accounting=yes \
    radius-interim-update=1m \
    radius-default-domain="" \
    nas-port-type=19
```

**Key Settings:**
- `use-radius=yes`: Enable RADIUS authentication
- `radius-accounting=yes`: Enable RADIUS accounting
- `radius-interim-update=1m`: Send updates every minute

## Step 4: Enable Accounting on Hotspot Server
```
/ip hotspot
set [ find default=yes ] \
    addresses-per-mac=2 \
    use-radius=yes
```

## Step 5: Test Configuration
```
/radius monitor 0
```

**Expected Output:**
```
         status: running
     dead-time: 0s
       timeout: 300ms
    max-retries: 3
     pending: 0
```

## Step 6: Enable Debug Logging
```
/system logging
add topics=radius,debug prefix=RADIUS
```

## Step 7: Create Test User
```
/ip hotspot user
add name=testuser password=testpass profile=default
```

## Step 8: Monitor Logs During Testing
```
/log print follow where topics~"^hotspot|^radius"
```

## Step 9: Verify Accounting Data
After a user connects, check Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "Accounting"
```

## Troubleshooting

### Issue: Authentication Fails
**Check:**
```
/radius print
```
**Verify:**
- IP address is correct
- Secret matches FreeRADIUS
- Router can reach Ubuntu server

### Issue: No Accounting Data
**Check:**
```
/ip hotspot profile print
```
**Verify:**
- `radius-accounting=yes`
- `radius-interim-update` is set

### Issue: Connection Timeouts
**Adjust:**
```
/radius
set timeout=1000ms
set max-retries=5
```

### Test RADIUS Authentication
```
/tool radius-test server=142.93.47.189 secret=YourSharedSecret123 \
    username=testuser password=testpass
```

**Expected:** `Access-Accept`