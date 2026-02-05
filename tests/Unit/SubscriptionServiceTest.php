<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_consume_rollover_on_purchase_applies_and_clears_user_rollover()
    {
        // Ensure the radgroupreply table exists for Plan model hooks
        if (! Schema::hasTable('radgroupreply')) {
            Schema::create('radgroupreply', function (Blueprint $table) {
                $table->id();
                $table->string('groupname');
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        $plan = Plan::create(['name' => 'RolloverPlan', 'validity_days' => 7, 'price' => 0, 'data_limit' => 100, 'limit_unit' => 'MB']);
        $user = User::factory()->create([
            'username' => 'rollover_user',
            'rollover_available_bytes' => 50 * 1048576, // 50 MB
            'rollover_validity_days' => 7,
        ]);

        $service = new SubscriptionService();
        $applied = $service->consumeRolloverOnPurchase($user, $plan);

        $this->assertEquals(50 * 1048576, $applied);
        $user->refresh();
        $this->assertEquals(0, $user->rollover_available_bytes);
        $this->assertNull($user->rollover_validity_days);
    }
}
