<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Device;

class NetworkControllerDisconnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_disconnect_marks_device_disconnected_and_clears_session()
    {
        $user = User::factory()->create(['username' => 'disconnect_user']);

        $mac = 'DE:AD:BE:EF:00:01';

        $device = Device::create([
            'user_id' => $user->id,
            'mac' => $mac,
            'is_connected' => true,
        ]);

        // Ensure radacct table exists in the test database
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

        // Simulate an active radacct row for this user/device
        \DB::table('radacct')->insert([
            'username' => $user->username,
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => now()->subMinutes(1),
            'acctstoptime' => null,
            'callingstationid' => $mac,
            'framedipaddress' => '198.51.100.10',
            'nasipaddress' => '10.0.0.1',
        ]);

        // Call disconnect route with session current_device_mac set
        $response = $this->withSession(['current_device_mac' => $mac])
            ->actingAs($user)
            ->post(route('user.disconnect'));

        $response->assertRedirect();

        $this->assertFalse($device->fresh()->is_connected);
        $this->assertNull(session('current_device_mac'));

        // Ensure radacct row was closed (acctstoptime not null)
        $this->assertDatabaseHas('radacct', [
            'username' => $user->username,
            'callingstationid' => $mac,
        ]);

        $row = \DB::table('radacct')->where('username', $user->username)->where('callingstationid', $mac)->first();
        $this->assertNotNull($row->acctstoptime);
    }
}
