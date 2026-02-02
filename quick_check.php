<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\RadAcct;

echo "=== Available Users ===\n";
$users = User::select('id', 'username', 'name')->take(10)->get();
foreach ($users as $user) {
    echo "{$user->id}: {$user->username} ({$user->name})\n";
}

echo "\n=== Checking RADIUS Sessions for First User ===\n";
$firstUser = User::first();
if ($firstUser) {
    echo "Checking user: {$firstUser->username}\n\n";

    $activeSessions = RadAcct::forUser($firstUser->username)
        ->active()
        ->get();

    echo "Active sessions: {$activeSessions->count()}\n";

    if ($activeSessions->count() > 0) {
        foreach ($activeSessions as $session) {
            echo "- Session: {$session->acctsessionid}, Started: {$session->acctstarttime}, IP: {$session->framedipaddress}\n";
        }
    } else {
        echo "No active sessions found.\n";
    }
} else {
    echo "No users found.\n";
}