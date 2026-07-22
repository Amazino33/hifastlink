<?php

use App\Http\Livewire\CaptiveAuth;
use App\Models\Device;
use App\Models\Plan;
use App\Models\RadCheck;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Livewire\Livewire;

const CAPTIVE_LINK = 'http://login.wifi/login';
const CAPTIVE_MAC  = 'AA:BB:CC:DD:EE:FF';

// ── Helpers ───────────────────────────────────────────────────────────────────

function captivePlan(array $overrides = []): Plan
{
    return Plan::forceCreate(array_merge([
        'name'                 => 'CaptivePlan ' . Str::random(4),
        'price'                => 3000,
        'data_limit'           => null,
        'limit_unit'           => 'Unlimited',
        'validity_days'        => 30,
        'family_limit'         => 0,
        'is_active'            => true,
        'speed_limit_upload'   => 2048,
        'speed_limit_download' => 4096,
    ], $overrides));
}

function captiveUser(array $overrides = []): User
{
    return User::forceCreate(array_merge([
        'name'            => 'Captive User',
        'username'        => 'cap_' . Str::random(6),
        'email'           => Str::random(8) . '@captive.test',
        'phone'           => '+234' . rand(8000000000, 8999999999),
        'password'        => bcrypt('password'),
        'radius_password' => 'captivepass123',
        'plan_expiry'     => now()->addDays(30),
        'data_limit'      => null,
        'data_used'       => 0,
        'family_limit'    => 0,
    ], $overrides));
}

function captiveVoucher(array $overrides = []): Voucher
{
    return Voucher::forceCreate(array_merge([
        'code'          => 'VCH-' . strtoupper(Str::random(8)),
        'max_uses'      => 5,
        'used_count'    => 0,
        'is_unlimited'  => false,
        'data_limit_mb' => 500,
    ], $overrides));
}

// ── connect(): empty input ────────────────────────────────────────────────────

test('empty identifier shows validation error', function () {
    Livewire::test(CaptiveAuth::class)
        ->set('identifier', '   ')
        ->call('connect')
        ->assertSet('error', 'Please enter your phone number, email, username, or voucher code.')
        ->assertSet('noplan', false);
});

// ── connect(): user lookup ────────────────────────────────────────────────────

test('email match with active plan bridges to router', function () {
    $user = captiveUser();

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $user->email)
        ->call('connect')
        ->assertRedirect(route('captive.bridge'));

    expect(session('bridge_username'))->toBe($user->username);
});

test('phone match with active plan bridges to router', function () {
    $user = captiveUser(['phone' => '+2348012345678']);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', '08012345678') // local format → matched via trailing-digit search
        ->call('connect')
        ->assertRedirect(route('captive.bridge'));

    expect(session('bridge_username'))->toBe($user->username);
});

test('username match with active plan bridges to router', function () {
    $user = captiveUser(['username' => 'testcap_user']);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', 'testcap_user')
        ->call('connect')
        ->assertRedirect(route('captive.bridge'));

    expect(session('bridge_username'))->toBe('testcap_user');
});

test('unknown identifier shows not-found error', function () {
    Livewire::test(CaptiveAuth::class)
        ->set('identifier', 'ghost@nowhere.test')
        ->call('connect')
        ->assertSet('error', 'No account found. Please subscribe at hifastlink.com first.');
});

test('user with expired plan sets noplan flag', function () {
    $user = captiveUser(['plan_expiry' => now()->subDay()]);

    Livewire::test(CaptiveAuth::class)
        ->set('identifier', $user->email)
        ->call('connect')
        ->assertSet('noplan', true)
        ->assertSet('error', '');
});

test('user with no radius credentials shows support error', function () {
    $user = captiveUser();
    // Simulate data corruption: RadCheck deleted and radius_password wiped.
    // Both must be absent because completeLogin() falls back to radius_password
    // if no RadCheck row exists.
    RadCheck::where('username', $user->username)->delete();
    $user->updateQuietly(['radius_password' => null]);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $user->email)
        ->call('connect')
        ->assertSet('error', 'Account not set up for hotspot access. Please contact support.');
});

test('user login without linkLogin redirects to dashboard', function () {
    $user = captiveUser();

    Livewire::test(CaptiveAuth::class)
        ->set('identifier', $user->email)
        ->call('connect')
        ->assertRedirect(route('dashboard'));
});

test('device is upserted on successful user login', function () {
    $user = captiveUser();

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('mac', CAPTIVE_MAC)
        ->set('identifier', $user->email)
        ->call('connect');

    expect(Device::where('mac', CAPTIVE_MAC)->where('user_id', $user->id)->exists())->toBeTrue();
});

// ── connect(): voucher ────────────────────────────────────────────────────────

test('valid voucher activates Cleartext-Password and Simultaneous-Use in RADIUS', function () {
    $voucher     = captiveVoucher(['max_uses' => 3]);
    $radUsername = 'vch_' . strtolower($voucher->code);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect');

    expect(
        RadCheck::where('username', $radUsername)->where('attribute', 'Cleartext-Password')->exists()
    )->toBeTrue();

    expect(
        RadCheck::where('username', $radUsername)
            ->where('attribute', 'Simultaneous-Use')
            ->where('value', '3')
            ->exists()
    )->toBeTrue();
});

test('valid voucher bridges to captive bridge route', function () {
    $voucher = captiveVoucher();

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect')
        ->assertRedirect(route('captive.bridge'));

    $radUsername = 'vch_' . strtolower($voucher->code);
    expect(session('bridge_username'))->toBe($radUsername);
});

test('invalid voucher code shows error', function () {
    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', 'VCH-DOESNTEXIST')
        ->call('connect')
        ->assertSet('error', 'This voucher is invalid, expired, or has no remaining uses.');
});

test('fully consumed voucher shows error', function () {
    $voucher = captiveVoucher(['max_uses' => 2, 'used_count' => 2]);

    Livewire::test(CaptiveAuth::class)
        ->set('identifier', $voucher->code)
        ->call('connect')
        ->assertSet('error', 'This voucher is invalid, expired, or has no remaining uses.');
});

test('voucher with expired plan on creator shows plan-expired error', function () {
    $creator = captiveUser(['plan_expiry' => now()->subDay()]);
    $voucher = captiveVoucher(['created_by' => $creator->id]);

    Livewire::test(CaptiveAuth::class)
        ->set('identifier', $voucher->code)
        ->call('connect')
        ->assertSet('error', "This voucher's plan has expired or run out of data.");
});

test('voucher without linkLogin shows reconnect error', function () {
    $voucher = captiveVoucher();

    Livewire::test(CaptiveAuth::class)
        ->set('identifier', $voucher->code)
        ->call('connect')
        ->assertSet('error', 'Voucher activated but no hotspot session found. Please reconnect to the WiFi.');
});

test('second redemption of same voucher reuses existing RADIUS password', function () {
    $voucher     = captiveVoucher(['max_uses' => 2]);
    $radUsername = 'vch_' . strtolower($voucher->code);

    RadCheck::create(['username' => $radUsername, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'stable-pass']);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect');

    $rad = RadCheck::where('username', $radUsername)->where('attribute', 'Cleartext-Password')->first();
    expect($rad->value)->toBe('stable-pass');
});

test('voucher sets RADIUS Expiration from creator plan_expiry', function () {
    $expiry  = now()->addDays(7);
    $creator = captiveUser(['plan_expiry' => $expiry, 'data_limit' => null]);
    $voucher = captiveVoucher(['created_by' => $creator->id]);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect');

    $radUsername = 'vch_' . strtolower($voucher->code);
    $expAttr     = RadCheck::where('username', $radUsername)->where('attribute', 'Expiration')->first();

    expect($expAttr)->not->toBeNull();
    expect($expAttr->value)->toBe($expiry->format('d M Y H:i'));
});

test('voucher with MAC stores device with voucher_code in meta', function () {
    $voucher = captiveVoucher();

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('mac', CAPTIVE_MAC)
        ->set('identifier', $voucher->code)
        ->call('connect');

    $device = Device::where('mac', CAPTIVE_MAC)->first();

    expect($device)->not->toBeNull();
    expect($device->user_id)->toBeNull();
    expect($device->meta['voucher_code'])->toBe($voucher->code);
});

test('voucher used_count increments on each redemption', function () {
    $voucher = captiveVoucher(['max_uses' => 3]);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect');

    expect($voucher->fresh()->used_count)->toBe(1);
});

// ── connect(): speed limits applied from plan ─────────────────────────────────

test('voucher with plan speed limits sets Mikrotik-Rate-Limit', function () {
    $plan    = captivePlan(['speed_limit_upload' => 1024, 'speed_limit_download' => 2048]);
    $voucher = captiveVoucher(['plan_id' => $plan->id]);

    Livewire::test(CaptiveAuth::class)
        ->set('linkLogin', CAPTIVE_LINK)
        ->set('identifier', $voucher->code)
        ->call('connect');

    $radUsername = 'vch_' . strtolower($voucher->code);

    expect(
        \App\Models\RadReply::where('username', $radUsername)
            ->where('attribute', 'Mikrotik-Rate-Limit')
            ->where('value', '1024k/2048k')
            ->exists()
    )->toBeTrue();
});

// ── MAC auto-login: controller layer (GET /login?mac=...) ────────────────────

test('known device with active user plan auto-bridges without showing form', function () {
    $user = captiveUser(['radius_password' => 'rpass']);
    Device::create([
        'user_id'    => $user->id,
        'mac'        => CAPTIVE_MAC,
        'first_seen' => now(),
        'last_seen'  => now(),
    ]);

    $this->get('/login?mac=' . CAPTIVE_MAC . '&link-login=' . urlencode(CAPTIVE_LINK))
        ->assertStatus(200)
        ->assertViewIs('hotspot.redirect_to_router');
});

test('known device with expired user plan falls through to captive form', function () {
    $user = captiveUser(['plan_expiry' => now()->subDay(), 'data_limit' => 500 * 1048576]);
    Device::create([
        'user_id'    => $user->id,
        'mac'        => CAPTIVE_MAC,
        'first_seen' => now(),
        'last_seen'  => now(),
    ]);

    $this->get('/login?mac=' . CAPTIVE_MAC . '&link-login=' . urlencode(CAPTIVE_LINK))
        ->assertStatus(200)
        ->assertViewIs('auth.captive-portal');
});

test('unknown MAC shows captive portal form', function () {
    $this->get('/login?mac=00:00:00:00:00:00&link-login=' . urlencode(CAPTIVE_LINK))
        ->assertStatus(200)
        ->assertViewIs('auth.captive-portal');
});

test('visiting login page without MAC shows captive portal form', function () {
    $this->get('/login')
        ->assertStatus(200)
        ->assertViewIs('auth.captive-portal');
});
