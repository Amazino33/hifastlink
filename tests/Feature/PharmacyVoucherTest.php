<?php

use App\Http\Livewire\PharmacyVoucher;
use App\Models\AppSetting;
use App\Models\Otp;
use App\Models\RadCheck;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// ── Helpers ──────────────────────────────────────────────────────

function setupPharmacyApi(): void
{
    AppSetting::set('basmelcare_api_url', 'https://api.basmelcare.test/verify');
    AppSetting::set('basmelcare_api_key', 'test-api-key-123');
}

function enableWhatsApp(): void
{
    AppSetting::set('wawp_enabled',      '1');
    AppSetting::set('wawp_instance_id',  'TESTINSTANCE');
    AppSetting::set('wawp_access_token', 'testtoken123');
    Http::fake(['api.wawp.net/*' => Http::response(['success' => true], 200)]);
}

function fakeValidInvoice(int $hours = 24): void
{
    Http::fake([
        'api.basmelcare.test/*' => Http::response([
            'valid'          => true,
            'validity_hours' => $hours,
            'expires_at'     => now()->addHours($hours)->toIso8601String(),
        ], 200),
    ]);
}

/**
 * Seed an OTP record directly — simulates WhatsAppService::sendOtp() having already run.
 * This avoids needing to enable WhatsApp delivery for verifyOtp() tests.
 */
function seedOtp(string $phone, string $code = '123456', int $minutesValid = 10): Otp
{
    return Otp::create([
        'phone'      => $phone,
        'otp'        => $code,
        'expires_at' => now()->addMinutes($minutesValid),
    ]);
}

// ── Invoice step ─────────────────────────────────────────────────

test('shows error when pharmacy integration is not configured', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('invoiceNumber', 'INV-001')
        ->call('validateInvoice')
        ->assertSet('step', 'invoice')
        ->assertSet('error', 'Pharmacy integration is not configured. Please contact support.');
});

test('shows API error message for invalid invoice', function () {
    setupPharmacyApi();

    Http::fake([
        'api.basmelcare.test/*' => Http::response([
            'valid'   => false,
            'message' => 'Receipt not found.',
        ], 200),
    ]);

    Livewire::test(PharmacyVoucher::class)
        ->set('invoiceNumber', 'INV-BOGUS')
        ->call('validateInvoice')
        ->assertSet('step', 'invoice')
        ->assertSet('error', 'Receipt not found.');
});

test('shows error when BasmelCare API is unreachable', function () {
    setupPharmacyApi();

    Http::fake([
        'api.basmelcare.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
    ]);

    Livewire::test(PharmacyVoucher::class)
        ->set('invoiceNumber', 'INV-001')
        ->call('validateInvoice')
        ->assertSet('step', 'invoice')
        ->assertSet('error', 'Could not reach BasmelCare. Please try again.');
});

test('advances to phone step on valid invoice', function () {
    setupPharmacyApi();
    fakeValidInvoice(24);

    Livewire::test(PharmacyVoucher::class)
        ->set('invoiceNumber', 'INV-20260706-0042')
        ->call('validateInvoice')
        ->assertSet('step', 'phone')
        ->assertSet('error', '')
        ->assertSet('validityHours', 24);
});

// ── Phone / OTP sending ──────────────────────────────────────────

test('shows error for too-short phone number', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->set('phone', '123')
        ->call('sendOtp')
        ->assertSet('error', 'Please enter a valid phone number.')
        ->assertSet('step', 'phone');
});

test('shows error when OTP rate limit is hit', function () {
    // Flood the OTP table with 3 recent records for this number
    $phone = '+2348012345678';
    for ($i = 0; $i < 3; $i++) {
        Otp::create(['phone' => $phone, 'otp' => '111111', 'expires_at' => now()->addMinutes(10)]);
    }

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->set('phone', '08012345678')
        ->call('sendOtp')
        ->assertSet('error', 'Too many attempts. Please wait a few minutes.')
        ->assertSet('step', 'phone');
});

test('shows error when WhatsApp and SMS are both disabled', function () {
    // No AppSettings for WhatsApp or SMS — send() returns false → sendOtp() returns null
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->set('phone', '08012345678')
        ->call('sendOtp')
        ->assertSet('error', 'Could not send OTP. Please try again.')
        ->assertSet('step', 'phone');
});

test('normalizes phone and advances to otp step when OTP is delivered', function () {
    enableWhatsApp();

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->set('phone', '08012345678') // local format
        ->call('sendOtp')
        ->assertSet('step', 'otp')
        ->assertSet('phone', '+2348012345678') // normalized
        ->assertSet('error', '');

    // OTP record written to DB
    expect(Otp::where('phone', '+2348012345678')->exists())->toBeTrue();
});

test('10-digit number without leading zero is normalized correctly', function () {
    enableWhatsApp();

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->set('phone', '8012345678') // 10 digits, no leading 0
        ->call('sendOtp')
        ->assertSet('step', 'otp')
        ->assertSet('phone', '+2348012345678');
});

// ── OTP verification ─────────────────────────────────────────────

test('shows error for OTP shorter than 6 digits', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '12345')
        ->call('verifyOtp')
        ->assertSet('error', 'Please enter a valid 6-digit code.');
});

test('shows error for non-numeric OTP', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', 'ABCDEF')
        ->call('verifyOtp')
        ->assertSet('error', 'Please enter a valid 6-digit code.');
});

test('shows error for wrong OTP code', function () {
    seedOtp('+2348012345678', '123456');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '999999')
        ->call('verifyOtp')
        ->assertSet('error', 'Invalid or expired code. Please try again.');
});

test('shows error for expired OTP', function () {
    Otp::create([
        'phone'      => '+2348012345678',
        'otp'        => '123456',
        'expires_at' => now()->subMinutes(1), // already expired
    ]);

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '123456')
        ->call('verifyOtp')
        ->assertSet('error', 'Invalid or expired code. Please try again.');
});

test('creates new user with RADIUS credentials on first-time OTP success', function () {
    seedOtp('+2348012345678');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '123456')
        ->call('verifyOtp');

    $user = User::where('phone', '+2348012345678')->first();
    expect($user)->not->toBeNull();
    expect($user->phone_verified_at)->not->toBeNull();

    expect(
        RadCheck::where('username', $user->username)
            ->where('attribute', 'Cleartext-Password')
            ->exists()
    )->toBeTrue();

    expect(
        RadCheck::where('username', $user->username)
            ->where('attribute', 'Simultaneous-Use')
            ->where('value', '1')
            ->exists()
    )->toBeTrue();
});

test('does not create a duplicate for a returning user', function () {
    $existing = User::forceCreate([
        'username'          => 'user_returning',
        'phone'             => '+2348099887766',
        'radius_password'   => 'existingpass',
        'phone_verified_at' => now(),
        'connection_status' => 'active',
    ]);

    seedOtp('+2348099887766');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348099887766')
        ->set('otp', '123456')
        ->call('verifyOtp');

    expect(User::where('phone', '+2348099887766')->count())->toBe(1);
    expect(User::where('phone', '+2348099887766')->first()->id)->toBe($existing->id);
});

test('finds user stored in old un-normalized format and migrates their phone', function () {
    // Insert directly so the mutator does not normalize it — simulating old bug data
    DB::table('users')->insert([
        'username'          => 'user_oldformat',
        'phone'             => '08055554444',
        'radius_password'   => 'oldpass',
        'phone_verified_at' => now(),
        'connection_status' => 'active',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);

    seedOtp('+2348055554444');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348055554444')
        ->set('otp', '123456')
        ->call('verifyOtp');

    // Same user found — no duplicate
    expect(User::where('phone', 'like', '%8055554444')->count())->toBe(1);

    // Phone migrated to canonical format
    expect(
        User::where('username', 'user_oldformat')->first()->getRawOriginal('phone')
    )->toBe('+2348055554444');
});

test('sets RADIUS Expiration attribute from BasmelCare expires_at', function () {
    $expiry = now()->addHours(24);

    seedOtp('+2348012345678');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '123456')
        ->set('expiresAt', $expiry->toIso8601String())
        ->call('verifyOtp');

    $user = User::where('phone', '+2348012345678')->firstOrFail();

    $expAttr = RadCheck::where('username', $user->username)
        ->where('attribute', 'Expiration')
        ->first();

    expect($expAttr)->not->toBeNull();
    expect($expAttr->value)->toBe($expiry->format('d M Y H:i'));
});

test('shows success step when not on captive portal', function () {
    seedOtp('+2348012345678');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '123456')
        ->call('verifyOtp')
        ->assertSet('step', 'success');
});

test('redirects to captive bridge when linkLogin is provided', function () {
    seedOtp('+2348012345678');

    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('phone', '+2348012345678')
        ->set('otp', '123456')
        ->set('linkLogin', 'http://login.wifi/login')
        ->set('mac', 'AA:BB:CC:DD:EE:FF')
        ->call('verifyOtp')
        ->assertRedirect(route('captive.bridge'));
});

// ── Navigation ───────────────────────────────────────────────────

test('goBack from otp step returns to phone step and clears code', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'otp')
        ->set('otp', '123456')
        ->call('goBack')
        ->assertSet('step', 'phone')
        ->assertSet('otp', '');
});

test('goBack from phone step returns to invoice step', function () {
    Livewire::test(PharmacyVoucher::class)
        ->set('step', 'phone')
        ->call('goBack')
        ->assertSet('step', 'invoice');
});

// ── Route ────────────────────────────────────────────────────────

test('GET /pharmacy-voucher is publicly accessible', function () {
    $this->get('/pharmacy-voucher')->assertStatus(200);
});
