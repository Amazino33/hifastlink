<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

echo "=== Data Exhaustion Test ===\n\n";

// Find a test user or use the first one
$user = User::whereNotNull('username')->first();

if (!$user) {
    echo "❌ No user found with username\n";
    exit(1);
}

echo "Testing with user: {$user->username} (ID: {$user->id})\n";
echo "Email: {$user->email}\n\n";

// Show current plan-related fields BEFORE exhaustion
echo "=== BEFORE Data Exhaustion ===\n";
echo "plan_id: " . ($user->plan_id ?? 'null') . "\n";
echo "data_plan_id: " . ($user->data_plan_id ?? 'null') . "\n";
echo "plan_expiry: " . ($user->plan_expiry ?? 'null') . "\n";
echo "plan_started_at: " . ($user->plan_started_at ?? 'null') . "\n";
echo "data_limit: " . ($user->data_limit ?? 'null') . "\n";
echo "data_used: " . ($user->data_used ?? 'null') . "\n";
echo "subscription_start_date: " . ($user->subscription_start_date ?? 'null') . "\n";
echo "subscription_end_date: " . ($user->subscription_end_date ?? 'null') . "\n";
echo "connection_status: " . ($user->connection_status ?? 'null') . "\n\n";

// Simulate data exhaustion by setting data_used >= data_limit
if ($user->plan_id) {
    $plan = Plan::find($user->plan_id);
    
    if ($plan) {
        echo "Found plan: {$plan->name}\n";
        echo "Plan data limit: {$plan->data_limit} {$plan->limit_unit}\n\n";
        
        // Set data_used to exceed limit
        $limitBytes = $plan->limit_unit === 'GB' ? $plan->data_limit * 1073741824 : $plan->data_limit * 1048576;
        $user->data_limit = $limitBytes;
        $user->data_used = $limitBytes + 1000000; // 1MB over
        $user->save();
        
        echo "✓ Set data_used to {$user->data_used} bytes (limit: {$limitBytes} bytes)\n";
        echo "✓ User has exceeded data limit: " . ($user->hasExceededDataLimit() ? 'YES' : 'NO') . "\n\n";
        
        // Refresh to get latest
        $user->refresh();
        
        // Now run the exhaustion logic manually
        echo "=== Running Data Exhaustion Logic ===\n";
        
        if ($user->hasExceededDataLimit() && $user->plan_id) {
            echo "✓ Detected data exhaustion, clearing ALL plan fields...\n";
            
            // Clear ALL plan-related fields (same as in SyncRadius command)
            $user->plan_id = null;
            $user->data_plan_id = null;
            $user->plan_expiry = null;
            $user->plan_started_at = null;
            $user->data_limit = 0; // Set to 0 instead of null (NOT NULL constraint)
            $user->subscription_start_date = null;
            $user->subscription_end_date = null;
            $user->connection_status = 'exhausted';
            $user->save();
            
            // Disconnect active sessions
            DB::table('radacct')
                ->where('username', $user->username)
                ->whereNull('acctstoptime')
                ->update([
                    'acctstoptime' => now(),
                    'acctterminatecause' => 'Data-Limit-Exceeded',
                ]);
            
            // Remove RADIUS credentials
            DB::table('radcheck')->where('username', $user->username)->delete();
            DB::table('radreply')->where('username', $user->username)->delete();
            
            echo "✓ Disconnected active sessions\n";
            echo "✓ Removed RADIUS credentials\n\n";
        }
        
        // Refresh to see final state
        $user->refresh();
        
        echo "=== AFTER Data Exhaustion ===\n";
        echo "plan_id: " . ($user->plan_id ?? 'null') . "\n";
        echo "data_plan_id: " . ($user->data_plan_id ?? 'null') . "\n";
        echo "plan_expiry: " . ($user->plan_expiry ?? 'null') . "\n";
        echo "plan_started_at: " . ($user->plan_started_at ?? 'null') . "\n";
        echo "data_limit: " . ($user->data_limit ?? 'null') . "\n";
        echo "data_used: " . ($user->data_used ?? '0') . " (kept for history)\n";
        echo "subscription_start_date: " . ($user->subscription_start_date ?? 'null') . "\n";
        echo "subscription_end_date: " . ($user->subscription_end_date ?? 'null') . "\n";
        echo "connection_status: " . ($user->connection_status ?? 'null') . "\n\n";
        
        // Verify all plan fields are cleared
        $allCleared = is_null($user->plan_id) && 
                   is_null($user->data_plan_id) && 
                   is_null($user->plan_expiry) && 
                   is_null($user->plan_started_at) && 
                   $user->data_limit == 0 &&
                   is_null($user->subscription_start_date) &&
                   is_null($user->subscription_end_date);
        
        if ($allCleared && $user->connection_status === 'exhausted') {
            echo "✅ SUCCESS: All plan-related fields cleared!\n";
        } else {
            echo "❌ FAILED: Some fields still have values\n";
            
            if (!is_null($user->plan_id)) echo "   - plan_id not null\n";
            if (!is_null($user->data_plan_id)) echo "   - data_plan_id not null\n";
            if (!is_null($user->plan_expiry)) echo "   - plan_expiry not null\n";
            if (!is_null($user->plan_started_at)) echo "   - plan_started_at not null\n";
            if ($user->data_limit != 0) echo "   - data_limit is {$user->data_limit} (should be 0)\n";
            if (!is_null($user->subscription_start_date)) echo "   - subscription_start_date not null\n";
            if (!is_null($user->subscription_end_date)) echo "   - subscription_end_date not null\n";
            if ($user->connection_status !== 'exhausted') echo "   - connection_status is not 'exhausted'\n";
        }
    } else {
        echo "❌ Plan not found\n";
    }
} else {
    echo "❌ User has no plan_id set, cannot test exhaustion\n";
}

echo "\n=== Test Complete ===\n";
