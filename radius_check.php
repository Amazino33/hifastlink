<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadAcct;

echo "=== RADIUS Database Connection Test ===\n";

try {
    // Test basic connection
    $count = RadAcct::count();
    echo "Total RADIUS records: $count\n\n";

    // Check for any active sessions
    $activeCount = RadAcct::active()->count();
    echo "Total active sessions: $activeCount\n\n";

    if ($activeCount > 0) {
        echo "=== Sample Active Sessions ===\n";
        $activeSessions = RadAcct::active()
            ->with(['username']) // This might not work due to different connections
            ->take(3)
            ->get();

        foreach ($activeSessions as $session) {
            echo "Username: {$session->username}\n";
            echo "Session ID: {$session->acctsessionid}\n";
            echo "Start Time: {$session->acctstarttime}\n";
            echo "IP: {$session->framedipaddress}\n";
            echo "Data: " . number_format(($session->acctinputoctets ?? 0) + ($session->acctoutputoctets ?? 0)) . " bytes\n";
            echo "---\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}