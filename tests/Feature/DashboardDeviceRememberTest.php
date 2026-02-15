<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Device;
use App\Models\RadAcct;
use Illuminate\Support\Facades\Hash;

class DashboardDeviceRememberTest extends TestCase
{
    use RefreshDatabase;

    public function test_cookie_based_device_remember_sets_session_current_device_mac()
    {
        $user = User::factory()->create(['username' => 'remember_user']);

        // Ensure radacct exists because UserDashboard queries radacct on render
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

        $device = Device::create([
            'user_id' => $user->id,
            'mac' => 'AA:BB:CC:11:22:33',
            'is_connected' => false,
        ]);

        // Simulate a previously-remembered browser by hashing a token into device.meta
        $token = bin2hex(random_bytes(16));
        $device->meta = ['browser_token_hash' => Hash::make($token)];
        $device->save();

        // Visit dashboard with the cookie set - UserDashboard should pick up the cookie and set session current_device_mac
        $this->withCookie('fastlink_device_token', $token)
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200);

        $this->assertEquals(session('current_device_mac'), $device->mac);
    }

    public function test_ip_autodetect_creates_device_and_sets_session()
    {
        $user = User::factory()->create(['username' => 'ip_detect_user']);

        // Ensure radacct table exists in the test database
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

        // Create a radacct active row that matches the test client's IP
        $ip = '203.0.113.5';
        $mac = '00:11:22:33:44:55';

        // Ensure acctterminatecause column exists when test DB provides an existing radacct table
        if (\Illuminate\Support\Facades\Schema::hasTable('radacct') && ! \Illuminate\Support\Facades\Schema::hasColumn('radacct', 'acctterminatecause')) {
            \Illuminate\Support\Facades\Schema::table('radacct', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('acctterminatecause')->nullable();
            });
        }

        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(5),
            'acctupdatetime' => now()->subMinutes(1),
            'acctstoptime' => null,
            'acctterminatecause' => null,
            'callingstationid' => $mac,
            'framedipaddress' => $ip,
            'nasipaddress' => '10.0.0.1',
        ]);

        // Ensure NAS table exists on the `radius` connection (Nas model uses connection 'radius')
        if (! \Illuminate\Support\Facades\Schema::connection('radius')->hasTable('nas')) {
            \Illuminate\Support\Facades\Schema::connection('radius')->create('nas', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('nasname');
                $table->string('shortname')->nullable();
                $table->timestamps();
            });
        }

        // Act as the user with REMOTE_ADDR equal to the radacct.framedipaddress
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(200);

        // Dashboard render should have stored current_device_mac in session and created/upserted a Device row
        $this->assertEquals(session('current_device_mac'), $mac);
        $this->assertDatabaseHas('devices', ['user_id' => $user->id, 'mac' => $mac]);
    }

    public function test_same_mac_auto_claims_device()
    {
        $user = User::factory()->create(['username' => 'auto_claim_user']);

        // Ensure radacct table exists in the test database
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

        $mac = 'AA:BB:CC:11:22:33';
        $ip = '203.0.113.6';

        // Pre-create a Device record for this user (no browser_token_hash yet)
        $device = Device::create([
            'user_id' => $user->id,
            'mac' => $mac,
            'is_connected' => false,
        ]);

        RadAcct::create([
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(5),
            'acctupdatetime' => now()->subMinutes(1),
            'acctstoptime' => null,
            'callingstationid' => $mac,
            'framedipaddress' => $ip,
            'nasipaddress' => '10.0.0.1',
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertStatus(200)->assertCookie('fastlink_device_token');

        $device = $device->fresh();
        $this->assertArrayHasKey('browser_token_hash', $device->meta ?? []);
        $this->assertEquals(session('current_device_mac'), $mac);
    }

    public function test_claim_link_only_shown_for_current_device()
    {
        $user = User::factory()->create(['username' => 'claim_visible_user']);

        $deviceA = Device::create(['user_id' => $user->id, 'mac' => 'AA:AA:AA:AA:AA:AA', 'is_connected' => false]);
        $deviceB = Device::create(['user_id' => $user->id, 'mac' => 'BB:BB:BB:BB:BB:BB', 'is_connected' => false]);

        $response = $this->withSession(['current_device_mac' => $deviceA->mac])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // After auto-claim behavior the current device will be remembered (so "Claim" is hidden)
        // and the UI shows the "Forget" link for the current browser/device. Non-current devices do not show a claim link.
        $this->assertEquals(0, substr_count($content, 'Claim this device'));
        $this->assertStringContainsString('(This browser/device)', $content);
        $this->assertStringContainsString('Forget', $content);
    }
}

