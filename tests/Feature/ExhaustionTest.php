<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserSession;

class ExhaustionTest extends TestCase
{
    use RefreshDatabase;

    public function test_interim_update_triggers_exhaustion_and_clears_rollover()
    {
        $user = User::factory()->create([
            'username' => 'exhaust_user',
            'data_limit' => 1000, // bytes for small test
            'data_used' => 990,
            'plan_id' => null,
            'rollover_available_bytes' => 5000, // should be cleared on exhaustion
            'connection_status' => 'active',
        ]);

        // Create an active session row which the controller will update
        UserSession::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'router_name' => 'test-router',
            'session_timestamp' => now(),
            'bytes_in' => 0,
            'bytes_out' => 0,
            'used_bytes' => 0,
            'limit_bytes' => $user->data_limit,
        ]);

        $response = $this->postJson('/api/radius/accounting', [
            'User-Name' => $user->username,
            'Acct-Status-Type' => 'Interim-Update',
            'Acct-Input-Octets' => 20,
            'Acct-Output-Octets' => 0,
        ]);

        $response->assertStatus(200);

        $user->refresh();

        $this->assertNull($user->plan_id);
        $this->assertEquals(0, $user->rollover_available_bytes);
        $this->assertEquals('exhausted', $user->connection_status);
    }
}
