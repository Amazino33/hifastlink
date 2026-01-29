# Complete MikroTik + FreeRADIUS Setup Guide

## 📋 PRE-REQUISITES
- MikroTik Router with RouterOS 6.0+
- FreeRADIUS server running on Ubuntu
- Network connectivity between router and server

## 🔧 STEP-BY-STEP CONFIGURATION

### Step 1: MikroTik Router Configuration

#### 1.1 Connect to Router
```
# Open Winbox or SSH to your router
# Go to Terminal/New Terminal
```

#### 1.2 Configure RADIUS Server
```
/radius
add service=hotspot address=142.93.47.189 secret=YourSharedSecret123
add service=ppp address=142.93.47.189 secret=YourSharedSecret123
```

#### 1.3 Configure Hotspot Profile for RADIUS
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

#### 1.4 Enable RADIUS on Hotspot Server
```
/ip hotspot
set [ find default=yes ] \
    addresses-per-mac=2 \
    use-radius=yes
```

#### 1.5 Set Default User Profile
```
/ip hotspot user profile
set [ find default=yes ] \
    rate-limit=10M/10M \
    session-timeout=1d \
    shared-users=1 \
    status-autorefresh=1m
```

### Step 2: FreeRADIUS Server Configuration

#### 2.1 Add MikroTik as RADIUS Client
Edit `/etc/freeradius/3.0/clients.conf`:
```
client mikrotik-router {
    ipaddr = 192.168.1.1  # Your router's IP
    secret = YourSharedSecret123
    require_message_authenticator = no
    nas_type = other
}
```

#### 2.2 Enable Accounting in Sites
Edit `/etc/freeradius/3.0/sites-enabled/default`:
```
accounting {
    detail {
        filename = ${radacctdir}/detail-%Y%m%d
    }

    exec {
        program = "/usr/bin/curl -X POST -H 'Content-Type: application/x-www-form-urlencoded' --data-binary @- http://your-hostinger-domain.com/api/radius/accounting"
        input_pairs = request
        output_pairs = reply
        wait = yes
        shell_escape = yes
    }

    sql {
        reference = "%{tolower:type.%{Acct-Status-Type}}"
    }

    radutmp
    sradutmp
}
```

#### 2.3 Restart FreeRADIUS
```bash
sudo systemctl restart freeradius
```

### Step 3: Testing the Setup

#### 3.1 Test RADIUS Connectivity
```
/tool radius-test server=142.93.47.189 secret=YourSharedSecret123 \
    username=testuser password=testpass
```

#### 3.2 Create Test User on Router
```
/ip hotspot user
add name=testuser password=testpass profile=default
```

#### 3.3 Monitor Logs
```
# On Ubuntu server
sudo tail -f /var/log/freeradius/radius.log

# On Laravel server
tail -f storage/logs/laravel.log | grep "Accounting"
```

#### 3.4 Connect Test User
- Connect a device to the hotspot
- Login with testuser/testpass
- Check if accounting data appears in logs

### Step 4: Verify Accounting Data

#### 4.1 Check RADIUS Database
```bash
mysql -u admin -p hifastlink -e "
SELECT username, acctstarttime, acctstoptime,
       acctinputoctets, acctoutputoctets,
       acctsessiontime, framedipaddress
FROM radacct
WHERE username='testuser'
ORDER BY acctstarttime DESC
LIMIT 1;"
```

#### 4.2 Check Laravel User Data
```bash
php artisan tinker
$user = User::where('username', 'testuser')->first();
echo "Data used: " . $user->data_used . " bytes\n";
echo "Status: " . $user->connection_status . "\n";
```

## 🔍 TROUBLESHOOTING

### Issue: Authentication Fails
**Check:**
- Router IP is in FreeRADIUS clients.conf
- Secrets match exactly
- No firewall blocking UDP ports 1812/1813

### Issue: No Accounting Data
**Check:**
- `radius-accounting=yes` in hotspot profile
- `radius-interim-update=1m` is set
- Laravel endpoint is accessible

### Issue: Data Not Updating in Laravel
**Check:**
- API route exists: `POST /api/radius/accounting`
- No authentication required on the endpoint
- Laravel logs show incoming requests

## 📊 ACCOUNTING DATA FLOW

1. **User Connects** → MikroTik sends Access-Request to FreeRADIUS
2. **Authentication** → FreeRADIUS checks database, responds Accept/Reject
3. **Session Start** → MikroTik sends Accounting-Start to FreeRADIUS
4. **Data Usage** → MikroTik sends Interim-Updates every minute
5. **Session End** → MikroTik sends Accounting-Stop with final usage
6. **Laravel Update** → FreeRADIUS forwards accounting data to Laravel API

## 🎯 SUCCESS INDICATORS

- ✅ `radtest` returns `Access-Accept`
- ✅ User can connect to hotspot
- ✅ Laravel logs show accounting packets
- ✅ User data usage increases in database
- ✅ Data limits are enforced automatically

## 📞 NEXT STEPS

1. Configure multiple routers if needed
2. Set up proper firewall rules
3. Implement data limit enforcement
4. Add user notifications
5. Set up monitoring and alerts

Your MikroTik router is now fully integrated with your HiFastLink RADIUS system! 🚀