<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class RadiusAccountingPhpTest extends TestCase
{
    use RefreshDatabase;

    public function test_interim_update_expires_subscription()
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

        // Create a plan first to satisfy foreign key constraint
        $plan = Plan::factory()->create();

        $user = User::factory()->create([
            'username' => 'testuser2',
            'data_limit' => 1000,
            'data_used' => 990,
            'plan_id' => $plan->id,
            'connection_status' => 'active',
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'session_timestamp' => now(),
            'router_name' => 'test_router',
            'bytes_in' => 0,
            'bytes_out' => 0,
            'used_bytes' => 0,
            'limit_bytes' => $user->data_limit,
        ]);

        $response = $this->postJson('/api/radius/accounting', [
            'User-Name' => $user->username,
            'Acct-Status-Type' => 'Interim-Update',
            'Acct-Input-Octets' => 15,
            'Acct-Output-Octets' => 0,
        ]);

        $response->assertStatus(200);

        $user->refresh();

        $this->assertNull($user->plan_id, 'plan_id should be null after exhaustion');
        $this->assertEquals('exhausted', $user->connection_status);
    }
}
