<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class RequestCustomPlansAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_master_can_view_request_custom_plans()
    {
        $user = User::factory()->create([
            'is_family_admin' => true,
            'parent_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('request-custom-plans'))
            ->assertStatus(200)
            ->assertSee('Request Custom Data Plans');
    }

    public function test_family_child_cannot_view_request_custom_plans()
    {
        $parent = User::factory()->create(['is_family_admin' => true, 'parent_id' => null]);
        $child = User::factory()->create(['parent_id' => $parent->id, 'is_family_admin' => false]);

        $this->actingAs($child)
            ->get(route('request-custom-plans'))
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_view_request_custom_plans()
    {
        $user = User::factory()->create(['is_family_admin' => false, 'parent_id' => null]);

        $this->actingAs($user)
            ->get(route('request-custom-plans'))
            ->assertStatus(403);
    }

    public function test_nav_link_visibility_for_family_master_and_hidden_for_regular_user()
    {
        $master = User::factory()->create(['is_family_admin' => true, 'parent_id' => null]);
        $this->actingAs($master)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Request Custom Plan');

        $user = User::factory()->create(['is_family_admin' => false, 'parent_id' => null]);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertDontSee('Request Custom Plan');
    }
}
