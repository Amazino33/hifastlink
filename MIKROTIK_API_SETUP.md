# MikroTik API Setup for Automatic User Disconnection

## Overview
When a user's data is exhausted, the system now automatically disconnects them from the MikroTik router instead of just blocking their internet access.

## Configuration Steps

### 1. Enable MikroTik REST API

Connect to your MikroTik router via Winbox or SSH and run:

```
/ip service enable api
/ip service enable api-ssl
```

Verify API is running:
```
/ip service print
```

You should see `api` and `api-ssl` services enabled.

### 2. Create API User (Optional but Recommended)

It's better to create a dedicated user for API access instead of using admin:

```
/user add name=hifastlink password=1a2345678B group=full
```

### 3. Configure Laravel Environment

Add these variables to your `.env` file:

```env
# MikroTik API Configuration
MIKROTIK_API_HOST=192.168.88.1
MIKROTIK_API_USER=admin
MIKROTIK_API_PASSWORD=your_mikrotik_password
```

**Important:** Replace with your actual router IP and credentials.

### 4. Test the Configuration

After setting up, when a user exhausts their data:

1. ✅ RADIUS credentials are cleared
2. ✅ Active RADIUS session is stopped
3. ✅ User is automatically disconnected from MikroTik router
4. ✅ No need to visit login.wifi/status to logout

### 5. Verify Disconnection

Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

Look for:
- `Data exhausted detected in dashboard for user {username}`
- `Successfully disconnected user {username} from MikroTik`

### 6. Manual Testing

You can trigger data exhaustion by:
1. Setting a very low data limit for a test plan (e.g., 1 MB)
2. Using data until exhausted
3. Refreshing dashboard - user should be disconnected automatically

## Security Notes

- **Never commit** your `.env` file to version control
- Use strong passwords for MikroTik API user
- Consider using API over HTTPS (api-ssl) for production
- Restrict API access to specific IP addresses if possible:

```
/ip service set api address=127.0.0.1,your-laravel-server-ip
```

## Troubleshooting

### API Connection Fails

1. **Check firewall:**
```
/ip firewall filter print where chain=input
```

2. **Test API manually:**
```bash
curl -u admin:password http://192.168.88.1/rest/ip/hotspot/active
```

3. **Check Laravel logs:**
```bash
tail -f storage/logs/laravel.log | grep MikroTik
```

### User Not Disconnected

- Verify API credentials in `.env` are correct
- Check MikroTik user has permissions: `/user print detail`
- Ensure MikroTik API service is running: `/ip service print`
- Check if username matches exactly (case-sensitive)

## API Endpoints Used

- **GET** `/rest/ip/hotspot/active` - List active sessions
- **DELETE** `/rest/ip/hotspot/active/{id}` - Remove specific session

## Fallback Behavior

If MikroTik API is not configured:
- System still clears RADIUS credentials (user can't re-authenticate)
- System still stops RADIUS session accounting
- User's internet access is blocked (but hotspot session remains)
- User can manually logout at `http://192.168.88.1/status`

## Reference

- [MikroTik REST API Documentation](https://help.mikrotik.com/docs/display/ROS/REST+API)
- [MikroTik Hotspot API](https://wiki.mikrotik.com/wiki/Manual:IP/Hotspot)

---

**Status:** ✅ Automatic disconnection ready - just add API credentials to `.env`
