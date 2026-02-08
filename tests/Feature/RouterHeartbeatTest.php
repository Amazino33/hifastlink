<?php

use App\Models\Router;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // ensure no heartbeat token set by default
    putenv('ROUTER_HEARTBEAT_TOKEN');
});

it('records heartbeat and updates last_seen_at', function () {
    $router = Router::create([
        'name' => 'Test Hub',
        'location' => 'Test Location',
        'ip_address' => '10.0.0.1',
        'nas_identifier' => 'router_test_1',
        'secret' => 'testsecret',
        'api_user' => null,
        'api_password' => null,
        'api_port' => 8728,
        'is_active' => true,
    ]);

    $this->getJson('/api/routers/heartbeat?identity=' . $router->nas_identifier)
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $router->refresh();

    expect($router->last_seen_at)->not->toBeNull();
    expect($router->last_seen_at->greaterThan(now()->subMinutes(1)))->toBeTrue();
});

it('returns 400 when missing identity', function () {
    $this->getJson('/api/routers/heartbeat')
        ->assertStatus(400)
        ->assertJson(['success' => false]);
});

it('rejects invalid token when ROUTER_HEARTBEAT_TOKEN is set', function () {
    putenv('ROUTER_HEARTBEAT_TOKEN=secret123');

    $router = Router::create([
        'name' => 'Test Hub 2',
        'location' => 'Test Location',
        'ip_address' => '10.0.0.2',
        'nas_identifier' => 'router_test_2',
        'secret' => 'testsecret',
        'api_user' => null,
        'api_password' => null,
        'api_port' => 8728,
        'is_active' => true,
    ]);

    $this->getJson('/api/routers/heartbeat?identity=' . $router->nas_identifier . '&token=wrong')
        ->assertStatus(401)
        ->assertJson(['success' => false]);

    putenv('ROUTER_HEARTBEAT_TOKEN');
});

it('accepts valid token when ROUTER_HEARTBEAT_TOKEN is set', function () {
    putenv('ROUTER_HEARTBEAT_TOKEN=secret123');

    $router = Router::create([
        'name' => 'Test Hub 3',
        'location' => 'Test Location',
        'ip_address' => '10.0.0.3',
        'nas_identifier' => 'router_test_3',
        'secret' => 'testsecret',
        'api_user' => null,
        'api_password' => null,
        'api_port' => 8728,
        'is_active' => true,
    ]);

    $this->getJson('/api/routers/heartbeat?identity=' . $router->nas_identifier . '&token=secret123')
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $router->refresh();
    expect($router->last_seen_at)->not->toBeNull();

    putenv('ROUTER_HEARTBEAT_TOKEN');
});
