<?php

use App\Models\Plan;
use App\Models\User;
use App\Models\Voucher;
use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Device;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// ── Helpers ──────────────────────────────────────────────────────

function createPlan(array $overrides = []): Plan
{
    return Plan::forceCreate(array_merge([
        'name'                 => 'Test Plan ' . Str::random(4),
        'price'                => 5000,
        'data_limit'           => 30,
        'limit_unit'           => 'GB',
        'validity_days'        => 30,
        'max_devices'          => 3,
        'speed_limit_upload'   => 2048,
        'speed_limit_download' => 4096,
        'is_active'            => true,
        'family_limit'         => 0,
    ], $overrides));
}

function createUser(array $overrides = []): User
{
    return User::forceCreate(array_merge([
        'name'            => 'Test User',
        'username'        => 'testuser_' . Str::random(6),
        'email'           => Str::random(8) . '@test.com',
        'phone'           => '+234' . rand(8000000000, 8999999999),
        'password'        => Hash::make('password'),
        'radius_password' => 'radius123',
        'phone_verified_at' => now(),
        'connection_status' => 'active',
        'family_limit'    => 0,
    ], $overrides));
}

function createVoucher(array $overrides = []): Voucher
{
    return Voucher::forceCreate(array_merge([
        'code'           => 'VCH-' . strtoupper(Str::random(8)),
        'max_uses'       => 3,
        'used_count'     => 0,
        'duration_hours' => 24,
        'is_unlimited'   => false,
        'data_limit_mb'  => 1024,
    ], $overrides));
}

// ── findValid() ──────────────────────────────────────────────────

test('findValid returns voucher when valid', function () {
    $v = createVoucher();
    expect(Voucher::findValid($v->code))->not->toBeNull();
});

test('findValid returns null when all slots used', function () {
    $v = createVoucher(['max_uses' => 1, 'used_count' => 1]);
    expect(Voucher::findValid($v->code))->toBeNull();
});

test('findValid returns null when expired', function () {
    $v = createVoucher(['expires_at' => now()->subHour()]);
    expect(Voucher::findValid($v->code))->toBeNull();
});

test('findValid returns voucher when expires_at is null (not yet redeemed)', function () {
    $v = createVoucher(['expires_at' => null]);
    expect(Voucher::findValid($v->code))->not->toBeNull();
});

test('findValid is case-insensitive', function () {
    $v = createVoucher(['code' => 'VCH-ABCD1234']);
    expect(Voucher::findValid('vch-abcd1234'))->not->toBeNull();
});

// ── consume() ────────────────────────────────────────────────────

test('consume increments used_count atomically', function () {
    $v = createVoucher(['max_uses' => 5, 'used_count' => 0]);

    $v->consume();
    $v->refresh();

    expect($v->used_count)->toBe(1);
    expect($v->used_at)->not->toBeNull();
});

test('consume sets expires_at on first use from duration_hours', function () {
    $v = createVoucher(['duration_hours' => 48, 'expires_at' => null]);

    $v->consume();
    $v->refresh();

    expect($v->expires_at)->not->toBeNull();
    expect($v->expires_at->diffInHours(now(), true))->toBeLessThan(49);
    expect($v->expires_at->diffInHours(now(), true))->toBeGreaterThan(47);
});

test('consume does not reset expires_at on subsequent uses', function () {
    $original = now()->addHours(10);
    $v = createVoucher(['duration_hours' => 48, 'expires_at' => $original, 'max_uses' => 5]);

    $v->consume();
    $v->refresh();

    expect($v->expires_at->format('Y-m-d H:i'))->toBe($original->format('Y-m-d H:i'));
});

test('consume does not increment past max_uses', function () {
    $v = createVoucher(['max_uses' => 1, 'used_count' => 1]);

    $v->consume();
    $v->refresh();

    expect($v->used_count)->toBe(1);
});

// ── Creator-based voucher expiry ─────────────────────────────────

test('creator-based voucher is valid when creator plan is active', function () {
    $plan = createPlan();
    $creator = createUser([
        'plan_id'      => $plan->id,
        'plan_expiry'  => now()->addDays(20),
        'data_used'    => 0,
        'data_limit'   => 32212254720,
    ]);
    $v = createVoucher(['created_by' => $creator->id]);

    $service = new SubscriptionService();
    expect($service->canConnectToHotspot($creator))->toBeTrue();
    expect(Voucher::findValid($v->code))->not->toBeNull();
});

test('creator-based voucher is blocked when creator plan expired', function () {
    $plan = createPlan();
    $creator = createUser(['plan_id' => $plan->id, 'data_used' => 0, 'data_limit' => 32212254720]);
    // Set expiry AFTER creation to avoid observer overwriting it
    $creator->updateQuietly(['plan_expiry' => now()->subDay()]);
    $creator->refresh();

    $service = new SubscriptionService();
    expect($service->canConnectToHotspot($creator))->toBeFalse();
});

test('creator-based voucher is blocked when creator data exhausted', function () {
    $plan = createPlan();
    $creator = createUser(['plan_id' => $plan->id, 'data_used' => 32212254720, 'data_limit' => 32212254720]);
    $creator->updateQuietly(['plan_expiry' => now()->addDays(20)]);

    $service = new SubscriptionService();
    expect($service->canConnectToHotspot($creator))->toBeFalse();
});

// ── Admin-created vouchers ───────────────────────────────────────

test('admin-created voucher with no creator is always valid', function () {
    $v = createVoucher(['created_by' => null]);
    expect(Voucher::findValid($v->code))->not->toBeNull();
    expect($v->creator)->toBeNull();
});

test('admin-created voucher respects its own expires_at', function () {
    $v = createVoucher(['created_by' => null, 'expires_at' => now()->subHour()]);
    expect(Voucher::findValid($v->code))->toBeNull();
});

// ── Voucher login (captive portal POST) ──────────────────────────

test('voucher login via captive portal sets up RADIUS credentials', function () {
    $plan = createPlan();
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(20),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher([
        'created_by'           => $creator->id,
        'speed_limit_upload'   => 1024,
        'speed_limit_download' => 2048,
        'data_limit_mb'        => 5120,
    ]);

    $response = $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
        'mac'      => 'AA:BB:CC:DD:EE:FF',
        'ip'       => '10.0.0.50',
    ]);

    // RADIUS credentials should exist
    expect(RadCheck::where('username', $v->code)->where('attribute', 'Cleartext-Password')->exists())->toBeTrue();
    expect(RadCheck::where('username', $v->code)->where('attribute', 'Simultaneous-Use')->exists())->toBeTrue();

    // Speed limits in radreply
    $rateLimit = RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Rate-Limit')->first();
    expect($rateLimit)->not->toBeNull();
    expect($rateLimit->value)->toBe('1024k/2048k');

    // Data cap in radreply
    $dataLimit = RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Total-Limit')->first();
    expect($dataLimit)->not->toBeNull();
    expect((int) $dataLimit->value)->toBe(5120 * 1048576);

    // Device saved
    $device = Device::where('mac', 'AA:BB:CC:DD:EE:FF')->first();
    expect($device)->not->toBeNull();
    expect($device->meta['voucher_code'])->toBe($v->code);
});

test('voucher login uses creator plan_expiry for RADIUS Expiration', function () {
    $plan = createPlan();
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(15),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher(['created_by' => $creator->id]);

    $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
    ]);

    $expiration = RadCheck::where('username', $v->code)->where('attribute', 'Expiration')->first();
    expect($expiration)->not->toBeNull();
    expect($expiration->value)->toBe($creator->plan_expiry->format('d M Y H:i'));
});

test('voucher login rejected when creator plan expired', function () {
    $plan = createPlan();
    $creator = createUser(['plan_id' => $plan->id, 'data_used' => 0, 'data_limit' => 32212254720]);
    $creator->updateQuietly(['plan_expiry' => now()->subDay()]);
    $v = createVoucher(['created_by' => $creator->id]);

    $response = $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
    ]);

    $response->assertSessionHasErrors('login');
    expect(RadCheck::where('username', $v->code)->exists())->toBeFalse();
});

test('admin voucher login works without creator', function () {
    $v = createVoucher(['created_by' => null]);

    $response = $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
        'mac'      => '11:22:33:44:55:66',
    ]);

    expect(RadCheck::where('username', $v->code)->where('attribute', 'Cleartext-Password')->exists())->toBeTrue();
});

// ── Plan-linked voucher inherits plan values ─────────────────────

test('plan-linked voucher uses plan speed limits when voucher has none', function () {
    $plan = createPlan(['speed_limit_upload' => 512, 'speed_limit_download' => 1024]);
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(20),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher([
        'created_by'           => $creator->id,
        'plan_id'              => $plan->id,
        'speed_limit_upload'   => null,
        'speed_limit_download' => null,
        'data_limit_mb'        => null,
    ]);

    $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
    ]);

    $rateLimit = RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Rate-Limit')->first();
    expect($rateLimit)->not->toBeNull();
    expect($rateLimit->value)->toBe('512k/1024k');
});

// ── Unlimited voucher ────────────────────────────────────────────

test('unlimited voucher removes data cap from RADIUS', function () {
    $plan = createPlan();
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(20),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher([
        'created_by'  => $creator->id,
        'is_unlimited' => true,
    ]);

    $this->post('/login', [
        'login'    => $v->code,
        'password' => $v->code,
    ]);

    expect(RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Total-Limit')->exists())->toBeFalse();
    expect(RadCheck::where('username', $v->code)->where('attribute', 'Mikrotik-Total-Limit')->exists())->toBeFalse();
});

// ── MAC auto-reconnect (Layer 1b) ───────────────────────────────

test('voucher MAC auto-reconnect works when RADIUS is intact', function () {
    $plan = createPlan();
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(20),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher(['created_by' => $creator->id]);

    // Set up RADIUS and device as if voucher was previously redeemed
    RadCheck::create(['username' => $v->code, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $v->code]);
    RadCheck::create(['username' => $v->code, 'attribute' => 'Simultaneous-Use', 'op' => ':=', 'value' => '3']);
    Device::forceCreate([
        'mac' => 'RE:CO:NN:EC:T0:01',
        'user_id' => null,
        'is_connected' => false,
        'meta' => ['voucher_code' => $v->code],
        'first_seen' => now(),
        'last_seen' => now()->subHour(),
    ]);

    $response = $this->get('/login?mac=RE:CO:NN:EC:T0:01&link-login=http://login.wifi/login&ip=10.0.0.99');

    $response->assertStatus(200);
    $response->assertSee($v->code);
});

test('voucher MAC auto-reconnect restores RADIUS after wipe', function () {
    $plan = createPlan(['speed_limit_upload' => 512, 'speed_limit_download' => 1024]);
    $creator = createUser([
        'plan_id'     => $plan->id,
        'plan_expiry' => now()->addDays(20),
        'data_used'   => 0,
        'data_limit'  => 32212254720,
    ]);
    $v = createVoucher([
        'created_by'           => $creator->id,
        'speed_limit_upload'   => 512,
        'speed_limit_download' => 1024,
        'data_limit_mb'        => 2048,
    ]);

    // Device exists but RADIUS was wiped
    Device::forceCreate([
        'mac' => 'WI:PE:D0:RA:DI:US',
        'user_id' => null,
        'is_connected' => false,
        'meta' => ['voucher_code' => $v->code],
        'first_seen' => now(),
        'last_seen' => now()->subHour(),
    ]);

    $this->get('/login?mac=WI:PE:D0:RA:DI:US&link-login=http://login.wifi/login');

    // Should have restored all RADIUS entries
    expect(RadCheck::where('username', $v->code)->where('attribute', 'Cleartext-Password')->exists())->toBeTrue();
    expect(RadCheck::where('username', $v->code)->where('attribute', 'Simultaneous-Use')->exists())->toBeTrue();

    $rateLimit = RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Rate-Limit')->first();
    expect($rateLimit)->not->toBeNull();
    expect($rateLimit->value)->toBe('512k/1024k');

    $dataLimit = RadReply::where('username', $v->code)->where('attribute', 'Mikrotik-Total-Limit')->first();
    expect($dataLimit)->not->toBeNull();
});

test('voucher MAC auto-reconnect fails when creator plan expired', function () {
    $plan = createPlan();
    $creator = createUser(['plan_id' => $plan->id, 'data_used' => 0, 'data_limit' => 32212254720]);
    $creator->updateQuietly(['plan_expiry' => now()->subDay()]);
    $v = createVoucher(['created_by' => $creator->id]);

    Device::forceCreate([
        'mac' => 'EX:PI:RE:DC:RE:AT',
        'user_id' => null,
        'is_connected' => false,
        'meta' => ['voucher_code' => $v->code],
        'first_seen' => now(),
        'last_seen' => now()->subHour(),
    ]);

    $response = $this->get('/login?mac=EX:PI:RE:DC:RE:AT&link-login=http://login.wifi/login');

    // Should NOT restore RADIUS — creator plan expired
    expect(RadCheck::where('username', $v->code)->exists())->toBeFalse();
});

// ── Voucher code detection ───────────────────────────────────────

test('isVoucherCode detects valid patterns', function () {
    expect(Voucher::isVoucherCode('VCH-ABCD1234'))->toBeTrue();
    expect(Voucher::isVoucherCode('vch-abcd1234'))->toBeTrue();
    expect(Voucher::isVoucherCode('VCH-A'))->toBeTrue();
});

test('isVoucherCode rejects invalid patterns', function () {
    expect(Voucher::isVoucherCode('08012345678'))->toBeFalse();
    expect(Voucher::isVoucherCode('test@email.com'))->toBeFalse();
    expect(Voucher::isVoucherCode('VCH-'))->toBeFalse();
    expect(Voucher::isVoucherCode('NOTAVOUCHER'))->toBeFalse();
});
