<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Device;
use App\Models\RadAcct;
use App\Services\RouterSessionService;
use Illuminate\Support\Facades\DB;

class ForceConnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_session_service_closes_open_radacct_sessions(): void
    {
        $username = 'victor_test';

        // Insert a simulated active session
        DB::table('radacct')->insert([
            'username'         => $username,
            'acctsessionid'    => 'session_12345',
            'acctuniqueid'     => 'unique_12345',
            'nasipaddress'     => '192.168.88.1',
            'nasportid'        => 'bridge',
            'acctstarttime'    => now()->subMinutes(10),
            'acctupdatetime'   => now()->subMinutes(5),
            'acctstoptime'     => null,
            'callingstationid' => 'AA:BB:CC:DD:EE:FF',
            'framedipaddress'  => '192.168.88.25',
        ]);

        $this->assertDatabaseHas('radacct', [
            'username'     => $username,
            'acctstoptime' => null,
        ]);

        $service = app(RouterSessionService::class);
        $result = $service->forceDisconnectUser($username, 'AA:BB:CC:DD:EE:FF');

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(1, $result['closed_sessions']);

        // Verify session is marked closed
        $this->assertDatabaseMissing('radacct', [
            'username'     => $username,
            'acctstoptime' => null,
        ]);
    }

    public function test_already_logged_in_error_triggers_force_connect_redirect(): void
    {
        $plan = Plan::create([
            'name'          => 'ActivePlan',
            'data_limit'    => 1000,
            'limit_unit'    => 'MB',
            'validity_days' => 30,
            'price'         => 1000,
        ]);

        $user = User::factory()->create([
            'username'        => 'victor',
            'email'           => 'victor@example.com',
            'radius_password' => 'secret123',
            'plan_id'         => $plan->id,
            'plan_expiry'     => now()->addDays(10),
            'data_limit'      => 1000 * 1048576,
            'data_used'       => 0,
        ]);

        $mac = '11:22:33:44:55:66';
        Device::create([
            'user_id'      => $user->id,
            'mac'          => $mac,
            'is_connected' => true,
        ]);

        // Insert active radacct session
        DB::table('radacct')->insert([
            'username'         => 'victor',
            'acctsessionid'    => 'sess_test',
            'acctuniqueid'     => 'uniq_test',
            'nasipaddress'     => '192.168.88.1',
            'nasportid'        => 'bridge',
            'acctstarttime'    => now()->subMinutes(5),
            'acctstoptime'     => null,
            'callingstationid' => $mac,
            'framedipaddress'  => '192.168.88.30',
        ]);

        // Request login with MikroTik "already logged in" error
        $response = $this->get('https://app.localhost/login?' . http_build_query([
            'link-login' => 'http://login.wifi/login',
            'mac'        => $mac,
            'username'   => 'victor',
            'error'      => 'already logged in',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('hotspot.redirect_to_router');
        $response->assertViewHas('force_connect', true);
        $response->assertViewHas('username', 'victor');

        // Verify active session was closed
        $this->assertDatabaseMissing('radacct', [
            'username'     => 'victor',
            'acctstoptime' => null,
        ]);
    }

    public function test_hotspot_force_connect_endpoint_clears_session(): void
    {
        $username = 'testuser';

        DB::table('radacct')->insert([
            'username'         => $username,
            'acctsessionid'    => 'sess_api',
            'acctuniqueid'     => 'uniq_api',
            'nasipaddress'     => '192.168.88.1',
            'nasportid'        => 'bridge',
            'acctstarttime'    => now()->subMinutes(2),
            'acctstoptime'     => null,
            'callingstationid' => 'AA:11:BB:22:CC:33',
            'framedipaddress'  => '192.168.88.40',
        ]);

        $response = $this->postJson(route('hotspot.force_connect'), [
            'username' => $username,
            'mac'      => 'AA:11:BB:22:CC:33',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('radacct', [
            'username'     => $username,
            'acctstoptime' => null,
        ]);
    }
}
