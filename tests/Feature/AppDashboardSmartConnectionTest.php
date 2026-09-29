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

        $connections = array_unique(array_filter([config('database.default'), 'radius']));
        foreach ($connections as $conn) {
            if (! Schema::connection($conn)->hasTable('radacct')) {
                Schema::connection($conn)->create('radacct', function ($table) {
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
            if (! Schema::connection($conn)->hasTable('radcheck')) {
                Schema::connection($conn)->create('radcheck', function ($table) {
                    $table->id();
                    $table->string('username');
                    $table->string('attribute');
                    $table->string('op')->default(':=');
                    $table->string('value');
                    $table->timestamps();
                });
            }
            if (! Schema::connection($conn)->hasTable('radreply')) {
                Schema::connection($conn)->create('radreply', function ($table) {
                    $table->id();
                    $table->string('username');
                    $table->string('attribute');
                    $table->string('op')->default(':=');
                    $table->string('value');
                    $table->timestamps();
                });
            }
            if (! Schema::connection($conn)->hasTable('radusergroup')) {
                Schema::connection($conn)->create('radusergroup', function ($table) {
                    $table->id();
                    $table->string('username');
                    $table->string('groupname');
                    $table->integer('priority')->default(1);
                    $table->timestamps();
                });
            }
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
        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now('UTC')->subMinutes(10),
            'acctupdatetime' => now('UTC')->subMinute(),
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
        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now('UTC')->subHour(),
            'acctupdatetime' => now('UTC')->subMinutes(15),
            'acctstoptime' => null,
            'callingstationid' => 'AA:BB:CC:DD:EE:02',
        ]);

        $this->actingAs($user);

        // AppDashboard should automatically clean the stale session and set state to plan-active
        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'plan-active')
            ->assertSeeHtml('id="app-connect-btn"');

        // Verify the stale session in the database was marked stopped
        $row = RadAcct::where('username', $user->username)->first();
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

        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now('UTC')->subMinutes(5),
            'acctupdatetime' => now('UTC')->subMinute(),
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
        $row = RadAcct::where('username', $user->username)->first();
        $this->assertNotNull($row->acctstoptime);
        $this->assertFalse($device->fresh()->is_connected);
    }

    public function test_reconnect_closes_previous_session_and_redirects()
    {
        $user = User::factory()->create([
            'username' => 'testuser5',
            'plan_expiry' => now()->addDays(5),
            'radius_password' => 'secret123',
        ]);

        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now('UTC')->subMinutes(2),
            'acctupdatetime' => now('UTC')->subSeconds(30),
            'acctstoptime' => null,
            'callingstationid' => 'AA:BB:CC:DD:EE:05',
        ]);

        $this->actingAs($user);
        $this->withSession(['current_device_mac' => 'AA:BB:CC:DD:EE:05']);

        Livewire::test(\App\Livewire\AppDashboard::class)
            ->call('reconnect')
            ->assertRedirect()
            ->assertSessionMissing('current_device_mac');

        // Verify old radacct was closed so FreeRADIUS Simultaneous-Use doesn't block re-authentication
        $row = RadAcct::where('username', $user->username)->first();
        $this->assertNotNull($row->acctstoptime, 'Old session must be closed on reconnect');
    }

    public function test_switching_ssid_with_new_mac_shows_plan_active()
    {
        $user = User::factory()->create([
            'username' => 'testuser6',
            'plan_expiry' => now()->addDays(5),
        ]);

        // Old session on old SSID / old MAC
        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now('UTC')->subMinutes(2),
            'acctupdatetime' => now('UTC')->subSeconds(10),
            'acctstoptime' => null,
            'callingstationid' => '11:22:33:44:55:66',
        ]);

        $this->actingAs($user);

        // User connects to new SSID where MikroTik redirects with new MAC ?mac=99:88:77:66:55:44
        $this->withSession(['current_device_mac' => '99:88:77:66:55:44']);

        // Since the current device MAC is not active in radacct, it should show plan-active (allowing user to tap Connect)
        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'plan-active')
            ->assertSeeHtml('id="app-connect-btn"');
    }

    public function test_confirm_connection_sets_state_to_connected()
    {
        $user = User::factory()->create([
            'username' => 'testuser7',
            'plan_expiry' => now()->addDays(5),
            'connection_status' => 'disconnected',
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\AppDashboard::class)
            ->assertSet('connectionState', 'plan-active')
            ->call('confirmConnection')
            ->assertSet('connectionState', 'connected');

        $this->assertEquals('active', $user->fresh()->connection_status);
    }
}
