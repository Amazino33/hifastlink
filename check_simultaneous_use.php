<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadAcct;

echo "\n🔍 Checking Simultaneous-Use Enforcement\n";
echo str_repeat("=", 80) . "\n\n";

$users = User::whereNotNull('username')->get();
$violations = 0;
$total = 0;

foreach ($users as $user) {
    $limit = RadCheck::where('username', $user->username)
        ->where('attribute', 'Simultaneous-Use')
        ->value('value');
    
    if (!$limit) {
        continue; // Skip users without limits
    }
    
    $total++;
    
    $activeSessions = RadAcct::where('username', $user->username)
        ->whereNull('acctstoptime')
        ->get();
    
    $active = $activeSessions->count();
    
    $status = '✅ OK';
    $color = '';
    
    if ($active > $limit) {
        $status = '❌ OVER LIMIT';
        $violations++;
        $color = "\033[1;31m"; // Red bold
        
        echo $color;
        echo sprintf(
            "%-20s | Limit: %-4s | Active: %-4s | %s\n",
            $user->username,
            $limit,
            $active,
            $status
        );
        echo "\033[0m"; // Reset color
        
        // Show session details
        echo "  📱 Active Sessions:\n";
        foreach ($activeSessions as $session) {
            echo sprintf(
                "     - IP: %-15s | NAS: %-15s | Started: %s\n",
                $session->framedipaddress,
                $session->nasipaddress,
                $session->acctstarttime->format('Y-m-d H:i:s')
            );
        }
        echo "\n";
    }
}

echo str_repeat("=", 80) . "\n";
echo "📊 Summary:\n";
echo "   Total users with limits: $total\n";
echo "   Users over limit: $violations\n";

if ($violations > 0) {
    echo "\n⚠️  WARNING: $violations user(s) have exceeded their device limits!\n";
    echo "\n📖 This means FreeRADIUS is NOT enforcing Simultaneous-Use.\n";
    echo "   Follow FREERADIUS_SIMULTANEOUS_USE.md to fix this.\n";
} else {
    echo "\n✅ All users are within their device limits.\n";
}

echo "\n";
