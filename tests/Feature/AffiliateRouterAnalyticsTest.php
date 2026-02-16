<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Router;

class AffiliateRouterAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_with_router_can_view_analytics()
    {
        $router = Router::create([ 'name' => 'Test Router', 'location' => 'Test Location', 'ip_address' => '192.0.2.1', 'nas_identifier' => 'router-test', 'secret' => 'secret' ]);
        $user = User::factory()->create(['router_id' => $router->id]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'affiliate']);
        $user->assignRole('affiliate');

        $this->actingAs($user)
            ->get(route('affiliate.router.analytics'))
            ->assertStatus(200)
            ->assertSee($router->name ?? $router->nas_identifier)
            ->assertSee('Router Analytics');
    }

    public function test_affiliate_sees_recent_sessions_on_analytics_page()
    {
        $router = Router::create([ 'name' => 'Test Router 3', 'location' => 'Test Location 3', 'ip_address' => '192.0.2.3', 'nas_identifier' => 'router-test-3', 'secret' => 'secret3' ]);
        $user = User::factory()->create(['router_id' => $router->id]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'affiliate']);
        $user->assignRole('affiliate');

        // Create a RadAcct session for this router
        \App\Models\RadAcct::create([
            'username' => 'sessionuser',
            'nasipaddress' => $router->ip_address,
            'callingstationid' => 'AA:BB:CC:11:22:33',
            'framedipaddress' => '10.0.0.5',
            'acctstarttime' => now()->subMinutes(10),
            'acctinputoctets' => 1024,
            'acctoutputoctets' => 2048,
        ]);

        $this->actingAs($user)
            ->get(route('affiliate.router.analytics'))
            ->assertStatus(200)
            ->assertSee('sessionuser')
            ->assertSee('AA:BB:CC:11:22:33')
            ->assertSee('10.0.0.5');
    }

    public function test_non_affiliate_cannot_view_affiliate_analytics()
    {
        $router = Router::create([ 'name' => 'Test Router 2', 'location' => 'Test Location 2', 'ip_address' => '192.0.2.2', 'nas_identifier' => 'router-test-2', 'secret' => 'secret2' ]);
        $user = User::factory()->create(['router_id' => $router->id]);

        $this->actingAs($user)
            ->get(route('affiliate.router.analytics'))
            ->assertStatus(403);
    }

    public function test_affiliate_without_router_gets_404()
    {
        $user = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'affiliate']);
        $user->assignRole('affiliate');

        $this->actingAs($user)
            ->get(route('affiliate.router.analytics'))
            ->assertStatus(404);
    }
}
