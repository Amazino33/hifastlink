<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Models\RadReply;
use App\Models\RadUserGroup;

class SubscriptionServiceExpireTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_for_expiry_snapshots_rollover_and_updates_radius_entries()
    {
        // Ensure radgroupreply/radreply/radusergroup tables exist because Plan::saved() and SubscriptionService touch them
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

        if (! \Illuminate\Support\Facades\Schema::hasTable('radreply')) {
            \Illuminate\Support\Facades\Schema::create('radreply', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username')->nullable();
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('radusergroup')) {
            \Illuminate\Support\Facades\Schema::create('radusergroup', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(1);
                $table->timestamps();
            });
        }

        // Ensure radgroupreply/radreply/radusergroup tables exist because Plan::saved() and SubscriptionService touch them
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

        if (! \Illuminate\Support\Facades\Schema::hasTable('radreply')) {
            \Illuminate\Support\Facades\Schema::create('radreply', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username')->nullable();
                $table->string('attribute');
                $table->string('op');
                $table->string('value');
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('radusergroup')) {
            \Illuminate\Support\Facades\Schema::create('radusergroup', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(1);
                $table->timestamps();
            });
        }

        // Ensure radacct exists for any PlanSyncService usage triggered by observers
        if (! \Illuminate\Support\Facades\Schema::hasTable('radacct')) {
            \Illuminate\Support\Facades\Schema::create('radacct', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->timestamp('acctstarttime')->nullable();
                $table->timestamp('acctupdatetime')->nullable();
                $table->timestamp('acctstoptime')->nullable();
                $table->string('acctterminatecause')->nullable();
                $table->string('callingstationid')->nullable();
                $table->string('framedipaddress')->nullable();
                $table->string('nasipaddress')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
            });
        }

        $plan = Plan::create([
            'name' => 'ExpiryPlan2',
            'data_limit' => 100, // MB
            'limit_unit' => 'MB',
            'validity_days' => 7,
            'price' => 0,
        ]);

        $user = User::factory()->create([
            'username' => 'expire_user',
            'plan_id' => $plan->id,
            'data_limit' => 100 * 1048576,
            'data_used' => 20 * 1048576,
            'plan_expiry' => now()->subMinutes(5),
        ]);

        $service = new \App\Services\SubscriptionService();
        $service->expireForExpiry($user);

        $user->refresh();

        // Rollover snapshot should be present
        $this->assertEquals(80 * 1048576, $user->rollover_available_bytes);
        $this->assertEquals($plan->validity_days, $user->rollover_validity_days);

        // Mikrotik total limit must be set to 0
        $rad = RadReply::where('username', $user->username)->where('attribute', 'Mikrotik-Total-Limit')->first();
        $this->assertNotNull($rad);
        $this->assertEquals('0', $rad->value);

        // RadUserGroup should be default_group
        $group = RadUserGroup::where('username', $user->username)->first();
        $this->assertNotNull($group);
        $this->assertEquals('default_group', $group->groupname);

        // Connection status must be set to inactive
        $this->assertEquals('inactive', $user->connection_status);
    }
}
