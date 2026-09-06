<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class RequestCustomPlansAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_master_cannot_view_request_custom_plans()
    {
        $user = User::factory()->create([
            'is_family_admin' => true,
            'parent_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('request-custom-plans'))
            ->assertStatus(403);
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

    public function test_custom_plan_link_hidden_for_non_affiliate_and_visible_for_affiliate()
    {
        $master = User::factory()->create(['is_family_admin' => true, 'parent_id' => null]);
        $this->actingAs($master)
            ->get(route('app.home'))
            ->assertStatus(200)
            ->assertDontSee('Custom Plan');

        $user = User::factory()->create(['is_family_admin' => false, 'parent_id' => null]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'affiliate']);
        $user->assignRole('affiliate');

        $this->actingAs($user)
            ->get(route('app.home'))
            ->assertStatus(200)
            ->assertSee('Custom Plan');
    }

    public function test_affiliate_can_view_request_custom_plans_and_sees_link()
    {
        $user = User::factory()->create(['is_family_admin' => false, 'parent_id' => null]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'affiliate']);
        $user->assignRole('affiliate');

        $this->actingAs($user)
            ->get(route('request-custom-plans'))
            ->assertStatus(200)
            ->assertSee('Request Custom Data Plans');

        $this->actingAs($user)
            ->get(route('app.home'))
            ->assertStatus(200)
            ->assertSee('Custom Plan');
    }
}
