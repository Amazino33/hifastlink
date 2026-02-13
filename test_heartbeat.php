<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Router;

// Test heartbeat API
$router = Router::first();
if ($router) {
    echo "Testing heartbeat for router: {$router->name} (NAS: {$router->nas_identifier})\n";
    echo "Current last_seen_at: " . ($router->last_seen_at ?? 'null') . "\n";
    echo "Is online: " . ($router->is_online ? 'Yes' : 'No') . "\n";

    // Simulate heartbeat call
    $url = "http://localhost/api/routers/heartbeat?identity={$router->nas_identifier}";
    echo "Heartbeat URL: $url\n";
    echo "You can test this URL in your browser or with curl\n";
} else {
    echo "No routers found in database\n";
}