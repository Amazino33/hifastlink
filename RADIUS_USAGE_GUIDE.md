# RADIUS Integration Usage Guide

## Configuration Complete ✅

Your Laravel app is now configured to connect to the remote MySQL RADIUS database at `142.93.47.189`.

### Database Connection

The `radius` connection has been added to `config/database.php`:

```php
'radius' => [
    'driver' => 'mysql',
    'host' => '142.93.47.189',
    'port' => '3306',
    'database' => 'hifastlink',
    'username' => 'admin',
    'password' => 'YOUR_RADIUS_PASSWORD', // ⚠️ Replace with actual password
    // ...
]
```

**Important**: Update the password in `config/database.php` with your actual MySQL password.

## Models Created

Three Eloquent models have been created to interact with the RADIUS database:

1. **RadCheck** - Stores user credentials and limits
2. **RadReply** - Stores RADIUS reply attributes (speed limits, session timeouts)
3. **RadAcct** - Stores accounting/usage data

## RadiusService Usage

A comprehensive service class has been created at `app/Services/RadiusService.php` to handle all RADIUS operations.

### 1. Create RADIUS User

When a user subscribes to a plan:

```php
use App\Services\RadiusService;

$radiusService = app(RadiusService::class);
$user = User::find(1);

// Create RADIUS credentials
$radiusService->createRadiusUser($user);
```

This will:
- Create password entry in `radcheck`
- Add data limit to `radcheck`
- Add speed limit to `radreply` (if plan has one)
- Add session timeout to `radreply` (if plan has duration)

### 2. Subscribe User to Plan

```php
$user = User::find(1);
$plan = DataPlan::find(2);

$radiusService->subscribeUserToPlan($user, $plan);
```

This will:
- Update user's data plan
- Set subscription dates
- Reset data usage
- Create RADIUS credentials
- Apply plan limits and speeds

### 3. Sync Data Usage

Get latest usage from RADIUS accounting:

```php
$user = User::find(1);

// Sync data usage from radacct table
$radiusService->syncUserDataUsage($user);

// User's data_used field is now updated
echo $user->formatted_data_used; // e.g., "1.5 GB"
```

### 4. Check User Status

```php
$user = User::find(1);

// Update connection status (online/offline)
$radiusService->updateConnectionStatus($user);

// Check if subscription is active
if ($user->isSubscriptionActive()) {
    echo "Active subscription";
}

// Check if data limit exceeded
if ($user->hasExceededDataLimit()) {
    echo "Data limit exceeded!";
    $radiusService->disableUser($user);
}
```

### 5. Get User Statistics

```php
$user = User::find(1);

$stats = $radiusService->getUserDataStats($user);

/*
Returns:
[
    'total_sessions' => 45,
    'active_sessions' => 1,
    'total_time_seconds' => 86400,
    'total_time_formatted' => "1d 0h 0m",
    'total_upload_bytes' => 1073741824,
    'total_download_bytes' => 3221225472,
    'total_data_bytes' => 4294967296,
    'total_data_formatted' => "4.00 GB",
    'last_session' => RadAcct object
]
*/
```

### 6. Get Active Session

```php
$user = User::find(1);

$activeSession = $radiusService->getActiveSession($user);

if ($activeSession) {
    echo "User is online";
    echo "IP: " . $activeSession->framedipaddress;
    echo "Upload: " . $activeSession->acctinputoctets . " bytes";
    echo "Download: " . $activeSession->acctoutputoctets . " bytes";
    echo "Duration: " . $activeSession->acctsessiontime . " seconds";
}
```

## Direct Model Usage

### Query RadCheck

```php
use App\Models\RadCheck;

// Get user's password
$password = RadCheck::where('username', 'testuser')
    ->where('attribute', 'Cleartext-Password')
    ->value('value');

// Get user's data limit
$dataLimit = RadCheck::where('username', 'testuser')
    ->where('attribute', 'Max-Octets')
    ->value('value');
```

### Query RadReply

```php
use App\Models\RadReply;

// Get user's speed limit
$speedLimit = RadReply::where('username', 'testuser')
    ->where('attribute', 'Mikrotik-Rate-Limit')
    ->value('value');

// Get session timeout
$timeout = RadReply::where('username', 'testuser')
    ->where('attribute', 'Session-Timeout')
    ->value('value');
```

### Query RadAcct

```php
use App\Models\RadAcct;

// Get all sessions for a user
$sessions = RadAcct::where('username', 'testuser')
    ->orderBy('acctstarttime', 'desc')
    ->get();

// Get only active sessions
$activeSessions = RadAcct::where('username', 'testuser')
    ->whereNull('acctstoptime')
    ->get();

// Get total data usage
$totalUsage = RadAcct::where('username', 'testuser')
    ->selectRaw('SUM(acctinputoctets + acctoutputoctets) as total')
    ->value('total');

echo "Total usage: " . ($totalUsage / 1073741824) . " GB";
```

## Console Command

A scheduled command has been created to sync data usage automatically.

### Manual Sync

```bash
# Sync all active users
php artisan radius:sync-data-usage

# Sync specific users by ID
php artisan radius:sync-data-usage --user=1 --user=2 --user=3
```

### Automatic Sync

The command runs automatically every 5 minutes via Laravel's scheduler (configured in `app/Console/Kernel.php`).

Make sure your cron is set up:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## User Model Enhancements

The User model has been enhanced with helper methods:

```php
$user = User::find(1);

// Relationships
$plan = $user->dataPlan; // Get user's data plan

// Status checks
$user->isSubscriptionActive(); // true/false
$user->hasExceededDataLimit(); // true/false

// Data attributes
$user->remaining_data; // Bytes remaining
$user->data_usage_percentage; // 0-100

// Formatted attributes
$user->formatted_data_used; // "1.5 GB"
$user->formatted_data_limit; // "5.0 GB"
$user->formatted_remaining_data; // "3.5 GB"
```

## DataPlan Model Enhancements

```php
$plan = DataPlan::find(1);

// Relationships
$users = $plan->users; // Get all users on this plan

// Duration helpers
$plan->duration_hours; // Duration in hours
$plan->duration_seconds; // Duration in seconds (for RADIUS)
```

## Testing Connection

Test the RADIUS database connection:

```bash
php artisan tinker
```

```php
// Test connection
DB::connection('radius')->getPdo();
// Should return PDO object without error

// Test query
\App\Models\RadAcct::count();
// Should return number of accounting records

// Test creating a user
$user = User::first();
app(\App\Services\RadiusService::class)->createRadiusUser($user);
// Should create entries in radcheck and radreply tables
```

## Common Operations

### When User Purchases a Plan

```php
$user = User::find(1);
$plan = DataPlan::find(2);
$radiusService = app(\App\Services\RadiusService::class);

// Subscribe user
$radiusService->subscribeUserToPlan($user, $plan);

// User can now connect via MikroTik hotspot
```

### When Checking Data Usage

```php
$user = User::find(1);
$radiusService = app(\App\Services\RadiusService::class);

// Sync latest data
$radiusService->syncUserDataUsage($user);

// Check if limit exceeded
if ($user->hasExceededDataLimit()) {
    // Disable user
    $radiusService->disableUser($user);
    
    // Send notification
    $user->notify(new DataLimitExceeded());
}
```

### When User's Subscription Expires

```php
$expiredUsers = User::where('subscription_end_date', '<', now())
    ->whereNotNull('subscription_end_date')
    ->get();

$radiusService = app(\App\Services\RadiusService::class);

foreach ($expiredUsers as $user) {
    // Disable RADIUS access
    $radiusService->disableUser($user);
    
    // Update status
    $user->update(['connection_status' => 'expired']);
    
    // Send notification
    $user->notify(new SubscriptionExpired());
}
```

## Troubleshooting

### Connection Issues

1. **Cannot connect to remote database**
   ```bash
   # Test connection from terminal
   mysql -h 142.93.47.189 -u admin -p hifastlink
   ```
   - Ensure firewall allows connections from your Hostinger IP
   - Check if MySQL port 3306 is open

2. **Authentication fails**
   - Verify password in `config/database.php`
   - Check MySQL user permissions: `GRANT ALL ON hifastlink.* TO 'admin'@'%';`

3. **Data not syncing**
   - Run manual sync: `php artisan radius:sync-data-usage`
   - Check Laravel logs: `tail -f storage/logs/laravel.log`
   - Verify cron is running: `php artisan schedule:list`

### Clear Config Cache

After making changes to `config/database.php`:

```bash
php artisan config:clear
php artisan config:cache
```

## Security Notes

1. **Never commit database credentials to git**
   - Use environment variables for production
   - Add `.env` to `.gitignore`

2. **Restrict MySQL access**
   - Only allow connections from your Hostinger IP
   - Use strong passwords
   - Consider SSH tunneling for added security

3. **Monitor access logs**
   - Check MySQL slow query log
   - Monitor failed connection attempts

## Next Steps

1. ✅ Update password in `config/database.php`
2. ✅ Test connection: `php artisan tinker` → `DB::connection('radius')->getPdo();`
3. ✅ Create a test user: `$radiusService->createRadiusUser($user);`
4. ✅ Verify in database: Check `radcheck` and `radreply` tables
5. ✅ Test MikroTik authentication
6. ✅ Set up cron for automatic syncing
7. ✅ Monitor logs for any issues

Your Laravel app is now fully integrated with your RADIUS infrastructure! 🎉
