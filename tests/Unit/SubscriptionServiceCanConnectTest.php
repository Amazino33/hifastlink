<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Services\SubscriptionService;

class SubscriptionServiceCanConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_active_plan_can_connect()
    {
        $plan = \App\Models\Plan::create(['name' => 'TestPlan', 'data_limit' => 100, 'limit_unit' => 'MB', 'validity_days' => 7, 'price' => 0]);

        $user = User::factory()->create([
            'plan_id' => $plan->id,
            'data_limit' => 100 * 1048576,
            'data_used' => 0,
            'plan_expiry' => now()->addDays(5),
        ]);

        $service = new SubscriptionService();
        $this->assertTrue($service->canConnectToHotspot($user));
    }

    public function test_child_allowed_when_family_master_has_active_plan()
    {
        $plan = \App\Models\Plan::create(['name' => 'MasterPlan', 'data_limit' => 100, 'limit_unit' => 'MB', 'validity_days' => 7, 'price' => 0]);

        $master = User::factory()->create([
            'plan_id' => $plan->id,
            'data_limit' => 100 * 1048576,
            'data_used' => 0,
            'plan_expiry' => now()->addDays(5),
        ]);

        $child = User::factory()->create([
            'parent_id' => $master->id,
            'plan_id' => null,
            'data_limit' => 0,
            'data_used' => 0,
            'plan_expiry' => null,
        ]);

        $service = new SubscriptionService();
        $this->assertTrue($service->canConnectToHotspot($child));
    }

    public function test_rollover_only_does_not_allow_connection()
    {
        $user = User::factory()->create([
            'plan_id' => null,
            'plan_expiry' => null,
            'data_limit' => 0,
            'data_used' => 0,
            'rollover_available_bytes' => 50 * 1048576,
            'rollover_validity_days' => 7,
        ]);

        $service = new SubscriptionService();
        $this->assertFalse($service->canConnectToHotspot($user));
    }
}
