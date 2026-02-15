<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Models\RadAcct;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DashboardProgressBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_family_usage_percent()
    {
        // Ensure the radgroupreply table exists because Plan::saved() touches it
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

        // Create a plan (100 MB) and a master user
        $plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'data_limit' => 100, // interpreted as 100 MB by storedValueToBytes
            'limit_unit' => 'MB',
            'validity_days' => 30,
        ]);

        $user = User::factory()->create([
            'username' => 'progress_user',
            'plan_id' => $plan->id,
            'data_limit' => 100,
            'data_used' => 0,
            'plan_expiry' => now()->addDays(30),
        ]);

        // Create a radacct entry representing 25 MB used (acctinputoctets + acctoutputoctets)
        $mb25 = 25 * 1048576;

        RadAcct::create([
            'acctuniqueid' => 'test_progress_' . time() . rand(1000, 9999),
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => now()->subMinutes(5),
            'acctstoptime' => null,
            'acctinputoctets' => $mb25,
            'acctoutputoctets' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('25%')
            ->assertSee('style="width: 25%"', false);
    }

    public function test_dashboard_includes_rollover_in_quota_and_percentage()
    {
        $plan = Plan::factory()->create([
            'name' => 'Rollover Plan',
            'data_limit' => 100, // 100 MB
            'limit_unit' => 'MB',
            'validity_days' => 30,
        ]);

        $user = User::factory()->create([
            'username' => 'rollover_user',
            'plan_id' => $plan->id,
            'data_limit' => 100,
            'data_used' => 0,
            'plan_expiry' => now()->addDays(30),
            // snapshot rollover present (50 MB)
            'rollover_available_bytes' => 50 * 1048576,
            'rollover_validity_days' => 30,
        ]);

        // Create radacct usage of 75 MB
        $mb75 = 75 * 1048576;
        RadAcct::create([
            'acctuniqueid' => 'test_rollover_' . time() . rand(1000, 9999),
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => now()->subMinutes(5),
            'acctstoptime' => null,
            'acctinputoctets' => $mb75,
            'acctoutputoctets' => 0,
        ]);

        // Effective quota = 100 MB plan + 50 MB rollover = 150 MB
        // Used 75 MB => 50%
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('50%')
            ->assertSee('style="width: 50%"', false)
            ->assertSee('150 MB');
    }
}

