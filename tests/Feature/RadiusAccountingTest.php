<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Plan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

uses(RefreshDatabase::class);

it('expires subscription on interim update and clears plan_id', function () {
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

    // Create a user with a small data limit and active plan
    $user = User::factory()->create([
        'username' => 'testuser',
        'data_limit' => 1000, // 1 KB
        'data_used' => 990,
        'plan_id' => $plan->id,
        'connection_status' => 'active',
    ]);

    // Create an active session
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

    // Send an interim update that pushes the usage over the limit
    $response = $this->postJson('/api/radius/accounting', [
        'User-Name' => $user->username,
        'Acct-Status-Type' => 'Interim-Update',
        'Acct-Input-Octets' => 15,
        'Acct-Output-Octets' => 0,
    ]);

    $response->assertStatus(200);

    $user->refresh();

    // Assert plan_id cleared and connection status set to exhausted
    expect($user->plan_id)->toBeNull();
    expect($user->connection_status)->toBe('exhausted');
});