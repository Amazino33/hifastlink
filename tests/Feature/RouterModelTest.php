<?php

use App\Models\Router;
use Illuminate\Support\Carbon;

it('is_online returns true when last_seen_at is within 5 minutes', function () {
    $router = Router::create([
        'name' => 'Online Hub',
        'location' => 'Test Location',
        'ip_address' => '10.0.1.1',
        'nas_identifier' => 'router_online_1',
        'secret' => 's',
        'last_seen_at' => now(),
    ]);

    expect($router->is_online)->toBeTrue();
});

it('is_online returns false when last_seen_at is older than 5 minutes', function () {
    $router = Router::create([
        'name' => 'Offline Hub',
        'location' => 'Test Location',
        'ip_address' => '10.0.1.2',
        'nas_identifier' => 'router_offline_1',
        'secret' => 's',
        'last_seen_at' => now()->subMinutes(10),
    ]);

    expect($router->is_online)->toBeFalse();
});

it('is_online returns false when last_seen_at is null', function () {
    $router = Router::create([
        'name' => 'NoSeen Hub',
        'location' => 'Test Location',
        'ip_address' => '10.0.1.3',
        'nas_identifier' => 'router_noseen_1',
        'secret' => 's',
        'last_seen_at' => null,
    ]);

    expect($router->is_online)->toBeFalse();
});
