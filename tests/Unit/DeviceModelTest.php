<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Device;
use App\Models\User;

class DeviceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_from_login_creates_or_updates_device()
    {
        $user = User::factory()->create(['username' => 'device_user']);

        $mac = 'AA:11:BB:22:CC:33';

        // Call upsertFromLogin - should create
        Device::upsertFromLogin($user, $mac, 'nas-xyz', '10.0.0.99', 'test-agent');

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'mac' => $mac,
        ]);

        $device = Device::where('mac', $mac)->first();
        $this->assertTrue($device->is_connected);
        $this->assertNotNull($device->last_seen);

        // Call upsertFromLogin again to simulate another login and ensure update path
        Device::upsertFromLogin($user, $mac, 'nas-xyz', '10.0.0.99', 'test-agent-2');

        $device = $device->fresh();
        $this->assertEquals('test-agent-2', $device->user_agent);
    }
}
