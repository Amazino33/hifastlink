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
}
