<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Models\PendingSubscription;

class ForceActivateFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_activate_family_plan_sets_user_as_family_admin_and_unlinks_children()
    {
        // Create a family-capable plan
        $plan = Plan::factory()->family(3)->create();

        // Create a user who is not currently a family admin and has one child
        $user = User::factory()->create(['is_family_admin' => false]);
        $child = User::factory()->create(['parent_id' => $user->id]);

        // Queue a pending subscription for the family plan
        $pending = PendingSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        // Authenticate as the user and invoke the Livewire method directly
        $this->actingAs($user);

        $component = new \App\Http\Livewire\UserDashboard();
        $component->forceActivate($pending->id);

        $user->refresh();
        $child->refresh();

        $this->assertTrue($user->is_family_admin, 'User should be family admin after activating a family plan');
        $this->assertNull($user->parent_id, 'Family admin must not have a parent_id');
        $this->assertEquals($plan->family_limit, $user->family_limit);

        // Any existing children should have been unlinked (parent_id => null)
        $this->assertNull($child->parent_id, 'Existing child should have been unlinked when master becomes family admin');
    }
}
