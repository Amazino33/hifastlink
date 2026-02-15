<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Plan;
use App\Models\User;
use App\Models\RadReply;
use App\Services\PlanSyncService;

class PlanSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollover_is_included_in_mikrotik_total_limit_when_validity_matches()
    {
        // Ensure radgroupreply/radreply/radusergroup tables exist (Plan::saved() and PlanSyncService write to them)
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

        // radacct is used by PlanSyncService to compute family usage
        if (! \Illuminate\Support\Facades\Schema::hasTable('radacct')) {
            \Illuminate\Support\Facades\Schema::create('radacct', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->timestamp('acctstarttime')->nullable();
                $table->timestamp('acctupdatetime')->nullable();
                $table->timestamp('acctstoptime')->nullable();
                $table->string('callingstationid')->nullable();
                $table->string('framedipaddress')->nullable();
                $table->string('nasipaddress')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
            });
        }
        $plan = Plan::create([
            'name' => 'RolloverMatch',
            'data_limit' => 100, // treated as MB by PlanSyncService
            'limit_unit' => 'MB',
            'validity_days' => 7,
            'price' => 0,
        ]);

        $user = User::factory()->create([
            'username' => 'ps_rollover_user',
            'plan_id' => $plan->id,
            // Pretend user has stored rollover bytes (in bytes)
            'rollover_available_bytes' => 50 * 1048576,
            'rollover_validity_days' => 7,
        ]);

        PlanSyncService::syncUserPlan($user);

        $rad = RadReply::where('username', $user->username)->where('attribute', 'Mikrotik-Total-Limit')->first();
        $this->assertNotNull($rad);

        $expected = (100 * 1024 * 1024) + (50 * 1048576); // plan MB -> bytes + rollover bytes
        $this->assertEquals((string) $expected, $rad->value);
    }

    public function test_rollover_is_not_included_when_validity_mismatch()
    {
        // Ensure supporting RADIUS tables exist in the in-memory DB for the Plan model hooks
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

        if (! \Illuminate\Support\Facades\Schema::hasTable('radacct')) {
            \Illuminate\Support\Facades\Schema::create('radacct', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->timestamp('acctstarttime')->nullable();
                $table->timestamp('acctupdatetime')->nullable();
                $table->timestamp('acctstoptime')->nullable();
                $table->string('callingstationid')->nullable();
                $table->string('framedipaddress')->nullable();
                $table->string('nasipaddress')->nullable();
                $table->bigInteger('acctinputoctets')->default(0);
                $table->bigInteger('acctoutputoctets')->default(0);
            });
        }

        $plan = Plan::create([
            'name' => 'RolloverMismatch',
            'data_limit' => 200,
            'limit_unit' => 'MB',
            'validity_days' => 30,
            'price' => 0,
        ]);

        $user = User::factory()->create([
            'username' => 'ps_rollover_user2',
            'plan_id' => $plan->id,
            'rollover_available_bytes' => 10 * 1048576,
            'rollover_validity_days' => 7, // does not match plan validity_days
        ]);

        PlanSyncService::syncUserPlan($user);

        $rad = RadReply::where('username', $user->username)->where('attribute', 'Mikrotik-Total-Limit')->first();
        $this->assertNotNull($rad);

        $expected = (200 * 1024 * 1024); // only the plan bytes, no rollover
        $this->assertEquals((string) $expected, $rad->value);
    }
}
