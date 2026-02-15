<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class CaptiveLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_captive_login_blocked_when_user_plan_expired()
    {
        $plan = \App\Models\Plan::create(['name' => 'ExpiredPlan', 'data_limit' => 10, 'limit_unit' => 'MB', 'validity_days' => 1, 'price' => 0]);

        $user = User::factory()->create([
            'email' => 'expired@example.com',
            'password' => bcrypt('password'),
            'plan_id' => $plan->id,
            'plan_expiry' => now()->subDays(1),
            'data_limit' => 100 * 1048576,
            'data_used' => 100 * 1048576,
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'link_login' => 'http://login.wifi/login',
        ]);

        // Should NOT leave the app and should NOT authenticate for captive connect
        $this->assertGuest();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('error', 'Please buy a plan.');
    }

    public function test_captive_login_allowed_when_family_master_active()
    {
        $plan = \App\Models\Plan::create(['name' => 'MasterPlan', 'data_limit' => 100, 'limit_unit' => 'MB', 'validity_days' => 7, 'price' => 0]);

        $master = User::factory()->create([
            'email' => 'master@example.com',
            'password' => bcrypt('password'),
            'radius_password' => 'password',
            'plan_id' => $plan->id,
            'data_limit' => 100 * 1048576,
            'data_used' => 0,
        ]);

        $child = User::factory()->create([
            'email' => 'child@example.com',
            'password' => bcrypt('password'),
            'radius_password' => 'password',
            'parent_id' => $master->id,
            'plan_expiry' => null,
            'data_limit' => 0,
            'data_used' => 0,
        ]);



        $response = $this->post('/login', [
            'login' => $child->email,
            'password' => 'password',
            'link_login' => 'http://login.wifi/login',
            'mac' => '00:11:22:33:44:55',
        ]);

        // Authenticated and redirected to the router-bridge view
        $this->assertAuthenticatedAs($child);
        $response->assertStatus(200);
        $response->assertViewIs('hotspot.redirect_to_router');
    }
}
