<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RadAcct;
use App\Models\User;

// Check for a specific user (you can change this to the actual username)
$username = 'your_username_here'; // Replace with actual username

$user = User::where('username', $username)->first();
if (!$user) {
    echo "User not found. Please set the correct username.\n";
    exit(1);
}

echo "Checking RADIUS sessions for user: {$user->username}\n\n";

echo "=== All Sessions (last 5) ===\n";
$sessions = RadAcct::forUser($user->username)
    ->latest('acctstarttime')
    ->take(5)
    ->get();

foreach ($sessions as $session) {
    echo "Session ID: {$session->acctsessionid}\n";
    echo "Start Time: {$session->acctstarttime}\n";
    echo "Stop Time: " . ($session->acctstoptime ?? 'NULL (Active)') . "\n";
    echo "IP: {$session->framedipaddress}\n";
    echo "Data Used: " . number_format(($session->acctinputoctets ?? 0) + ($session->acctoutputoctets ?? 0)) . " bytes\n";
    echo "---\n";
}

echo "\n=== Active Sessions Only ===\n";
$activeSessions = RadAcct::forUser($user->username)
    ->active()
    ->get();

if ($activeSessions->count() > 0) {
    foreach ($activeSessions as $session) {
        echo "ACTIVE Session ID: {$session->acctsessionid}\n";
        echo "Start Time: {$session->acctstarttime}\n";
        echo "IP: {$session->framedipaddress}\n";
        echo "---\n";
    }
} else {
    echo "No active sessions found.\n";
}

echo "\n=== Dashboard Query (Fixed) ===\n";
$dashboardSession = RadAcct::forUser($user->username)
    ->active()
    ->latest('acctstarttime')
    ->first();

if ($dashboardSession) {
    echo "Dashboard would detect: ACTIVE\n";
    echo "Session ID: {$dashboardSession->acctsessionid}\n";
    echo "Start Time: {$dashboardSession->acctstarttime}\n";
    echo "IP: {$dashboardSession->framedipaddress}\n";
} else {
    echo "Dashboard would detect: OFFLINE\n";
}