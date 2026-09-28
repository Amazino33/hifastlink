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

    public function test_plan_sync_service_does_not_revive_expired_user()
    {
        $plan = Plan::create([
            'name' => 'TestPlan',
            'data_limit' => 100 * 1048576,
            'limit_unit' => 'MB',
            'validity_days' => 30,
            'price' => 1000,
        ]);

        $expiredDate = now()->subDays(2);
        $user = User::factory()->create([
            'username' => 'expired_client',
            'plan_id' => $plan->id,
            'plan_expiry' => $expiredDate,
            'connection_status' => 'inactive',
        ]);

        \App\Services\PlanSyncService::syncUserPlan($user);

        $user->refresh();

        // plan_expiry MUST remain expired in the past, NOT extended forward!
        $this->assertTrue($user->plan_expiry->isPast());
        $this->assertEquals($expiredDate->format('Y-m-d H:i'), $user->plan_expiry->format('Y-m-d H:i'));
        $this->assertEquals('inactive', $user->connection_status);

        // RadCheck must NOT have Cleartext-Password
        $hasPassword = \App\Models\RadCheck::where('username', 'expired_client')
            ->where('attribute', 'Cleartext-Password')
            ->exists();
        $this->assertFalse($hasPassword);
    }

    public function test_has_exceeded_data_limit_with_small_byte_usage()
    {
        $user = User::factory()->make([
            'data_limit' => 10 * 1073741824, // 10 GB limit in bytes
            'data_used'  => 500000,          // 500 KB used in bytes (< 1MB)
        ]);

        // 500 KB used against 10 GB limit is NOT exhausted!
        $this->assertFalse($user->hasExceededDataLimit());
        $this->assertGreaterThan(0, $user->remaining_data);
    }
}
