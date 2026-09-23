<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Plan;
use App\Models\RadAcct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AppDashboardSmartConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('radacct')) {
            Schema::create('radacct', function ($table) {
                $table->id('radacctid');
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
                $table->integer('acctsessiontime')->default(0);
            });
        }
    }

    public function test_user_with_plan_and_no_session_shows_plan_active_state()
    {
        $user = User::factory()->create([
            'username' => 'testuser1',
            'plan_expiry' => now()->addDays(5),
            'data_limit' => 104857600,
            'data_used' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'plan-active')
            ->assertSeeHtml('id="app-connect-btn"');
    }

    public function test_user_with_active_session_shows_connected_state()
    {
        $user = User::factory()->create([
            'username' => 'testuser2',
            'plan_expiry' => now()->addDays(5),
        ]);

        // Insert active radacct row updated recently (within 1 min)
        DB::table('radacct')->insert([
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => now()->subMinute(),
            'acctstoptime' => null,
            'callingstationid' => 'AA:BB:CC:DD:EE:01',
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'connected')
            ->assertSeeHtml('conn-center-btn');
    }

    public function test_user_with_stale_session_is_not_treated_as_connected()
    {
        $user = User::factory()->create([
            'username' => 'testuser3',
            'plan_expiry' => now()->addDays(5),
        ]);

        // Insert STALE radacct row (last update was 15 minutes ago)
        DB::table('radacct')->insert([
            'username' => $user->username,
            'acctstarttime' => now()->subHour(),
            'acctupdatetime' => now()->subMinutes(15),
            'acctstoptime' => null,
            'callingstationid' => 'AA:BB:CC:DD:EE:02',
        ]);

        $this->actingAs($user);

        // AppDashboard should automatically clean the stale session and set state to plan-active
        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'plan-active')
            ->assertSeeHtml('id="app-connect-btn"');

        // Verify the stale session in the database was marked stopped
        $row = DB::table('radacct')->where('username', $user->username)->first();
        $this->assertNotNull($row->acctstoptime, 'Stale radacct row should have been closed');
    }

    public function test_disconnect_closes_session_and_switches_state_to_plan_active()
    {
        $user = User::factory()->create([
            'username' => 'testuser4',
            'plan_expiry' => now()->addDays(5),
        ]);

        $mac = 'AA:BB:CC:DD:EE:03';

        $device = Device::create([
            'user_id' => $user->id,
            'mac' => $mac,
            'is_connected' => true,
        ]);

        DB::table('radacct')->insert([
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(5),
            'acctupdatetime' => now()->subMinute(),
            'acctstoptime' => null,
            'callingstationid' => $mac,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'connected')
            ->call('disconnect')
            ->assertSet('connectionState', 'plan-active')
            ->assertDispatched('trigger-router-logout');

        // Check radacct closed
        $row = DB::table('radacct')->where('username', $user->username)->first();
        $this->assertNotNull($row->acctstoptime);
        $this->assertFalse($device->fresh()->is_connected);
    }
}
