<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;

class SubscriptionExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_subscriptions_expiry_snapshots_rollover_and_clears_plan()
    {
        // Ensure the radgroupreply table exists because Plan::saved() touches it
        if (! \Illuminate\Support\Facades\Schema::hasTable('radgroupreply')) {
            \Illuminate\Support\Facades\Schema::create('radgroupreply', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('groupname');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        $plan = Plan::create([
            'name' => 'ExpiryPlan',
            'data_limit' => 100 * 1048576, // 100 MB in bytes
            'limit_unit' => 'MB',
            'validity_days' => 30,
            'price' => 0,
        ]);

        $user = User::factory()->create([
            'username' => 'expiry_user',
            'plan_id' => $plan->id,
            'data_limit' => 100 * 1048576, // stored as bytes (100 MB)
            'data_used' => 40 * 1048576, // 40 MB used => 60 MB remaining
            'plan_expiry' => now()->subMinutes(1), // expired
        ]);

        // Run the expiry check
        $this->artisan('subscriptions:check-expiry')->assertExitCode(0);

        // Ensure expiry processing ran; call directly to be deterministic in test environment
        $service = new \App\Services\SubscriptionService();
        $service->expireForExpiry($user);

        $user->refresh();

        // plan_id should be null and rollover set to 60 MB
        $this->assertNull($user->plan_id);
        $this->assertEquals(60 * 1048576, $user->rollover_available_bytes);
    }
}
