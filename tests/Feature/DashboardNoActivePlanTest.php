<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class DashboardNoActivePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_no_active_plan_and_zero_usage_when_no_plan()
    {
        $user = User::factory()->create([
            'username' => 'no_plan_user',
            'data_limit' => 0, // no active plan stored as 0 in DB
            'data_used' => 5000000, // simulate previous usage
            'plan_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('No Active Plan')
            ->assertSee('0 B')
            ->assertDontSee('Unlimited');
    }
}
