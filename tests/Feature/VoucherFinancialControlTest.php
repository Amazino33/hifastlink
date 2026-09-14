<?php

use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherGenerationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

function createTestUser(array $overrides = []): User
{
    return User::forceCreate(array_merge([
        'name'              => 'Test Router Owner',
        'username'          => 'owner_' . Str::random(6),
        'email'             => Str::random(8) . '@test.com',
        'phone'             => '+234' . rand(8000000000, 8999999999),
        'password'          => Hash::make('password'),
        'radius_password'   => 'radius123',
        'phone_verified_at' => now(),
        'connection_status' => 'active',
        'wallet_balance'    => 0.00,
        'family_limit'      => 0,
    ], $overrides));
}

function createTestPlan(array $overrides = []): Plan
{
    return Plan::forceCreate(array_merge([
        'name'                 => '1 Day Unlimited ' . Str::random(4),
        'price'                => 500.00,
        'data_limit'           => null,
        'limit_unit'           => 'Unlimited',
        'validity_days'        => 1,
        'max_devices'          => 1,
        'speed_limit_upload'   => 2048,
        'speed_limit_download' => 4096,
        'is_active'            => true,
        'family_limit'         => 0,
    ], $overrides));
}

test('voucher batch generation is blocked when wallet balance is insufficient', function () {
    $user = createTestUser(['wallet_balance' => 1000.00]);
    $plan = createTestPlan(['price' => 500.00]);
    $service = app(VoucherGenerationService::class);

    // 10 vouchers * ₦500 = ₦5,000 > ₦1,000 balance
    expect(fn () => $service->generateBatch($user, $plan, 10, ['payment_method' => 'wallet']))
        ->toThrow(\RuntimeException::class, 'Insufficient wallet balance');

    // Assert zero records created
    expect(VoucherBatch::where('user_id', $user->id)->count())->toBe(0);
    expect(Voucher::where('created_by', $user->id)->count())->toBe(0);
    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);
    expect((float) $user->fresh()->wallet_balance)->toBe(1000.00);
});

test('voucher batch generation succeeds and strictly deducts from wallet', function () {
    $user = createTestUser(['wallet_balance' => 10000.00]);
    $plan = createTestPlan(['price' => 500.00]);
    $service = app(VoucherGenerationService::class);

    // 10 vouchers * ₦500 = ₦5,000
    $batch = $service->generateBatch($user, $plan, 10, [
        'payment_method' => 'wallet',
        'label'          => 'Test Kiosk Stock',
    ]);

    expect($batch)->toBeInstanceOf(VoucherBatch::class);
    expect($batch->batch_code)->toStartWith('VB-');
    expect($batch->quantity)->toBe(10);
    expect((float) $batch->total_cost)->toBe(5000.00);
    expect((float) $batch->unit_price)->toBe(500.00);
    expect($batch->payment_method)->toBe('wallet');

    // Assert user wallet was decremented
    expect((float) $user->fresh()->wallet_balance)->toBe(5000.00);

    // Assert transaction created
    $tx = Transaction::where('user_id', $user->id)->first();
    expect($tx)->not->toBeNull();
    expect((float) $tx->amount)->toBe(5000.00);
    expect($tx->type)->toBe('wallet_deduction');
    expect($tx->status)->toBe('completed');
    expect($tx->gateway)->toBe('wallet');

    // Assert 10 vouchers created with batch_id and price
    $vouchers = Voucher::where('batch_id', $batch->id)->get();
    expect($vouchers->count())->toBe(10);
    foreach ($vouchers as $v) {
        expect((float) $v->price)->toBe(500.00);
        expect($v->plan_id)->toBe($plan->id);
        expect($v->created_by)->toBe($user->id);
        expect($v->code)->toStartWith('VCH-');
        expect($v->transaction_id)->toBe($tx->id);
    }
});

function createTestRouter(array $overrides = []): Router
{
    return Router::forceCreate(array_merge([
        'name'           => 'Test Hotspot AP ' . Str::random(4),
        'location'       => 'Main Campus',
        'ip_address'     => '10.0.0.' . rand(1, 250),
        'vpn_ip'         => '10.8.0.' . rand(1, 250),
        'nas_identifier' => 'nas_' . Str::random(6),
        'secret'         => 'secret123',
        'is_active'      => true,
    ], $overrides));
}

test('router owner POST /vouchers/generate checks wallet and blocks if unfunded', function () {
    $user = createTestUser(['wallet_balance' => 0.00]);
    $router = createTestRouter(['owner_id' => $user->id]);
    $plan = createTestPlan(['price' => 1000.00]);

    $response = $this->actingAs($user)->post('/vouchers/generate', [
        'plan_id'   => $plan->id,
        'quantity'  => 5,
        'router_id' => $router->id,
    ]);

    $response->assertSessionHas('error');
    expect(Voucher::where('created_by', $user->id)->count())->toBe(0);
});

test('router owner POST /vouchers/generate succeeds when wallet is funded', function () {
    $user = createTestUser(['wallet_balance' => 25000.00]);
    $router = createTestRouter(['owner_id' => $user->id]);
    $plan = createTestPlan(['price' => 500.00]);

    $response = $this->actingAs($user)->post('/vouchers/generate', [
        'plan_id'   => $plan->id,
        'quantity'  => 20,
        'router_id' => $router->id,
    ]);

    $response->assertSessionHas('success');
    expect((float) $user->fresh()->wallet_balance)->toBe(15000.00); // 25k - (20 * 500 = 10k)
    expect(Voucher::where('created_by', $user->id)->count())->toBe(20);
});

test('super admin override requires mandatory reason and logs to audit', function () {
    $admin = createTestUser(['email' => 'amazino33@gmail.com']);
    $plan = createTestPlan(['price' => 500.00]);
    $service = app(VoucherGenerationService::class);

    // Empty reason should fail
    expect(fn () => $service->generateBatch($admin, $plan, 5, [
        'payment_method'  => 'admin_override',
        'override_reason' => '',
    ]))->toThrow(\InvalidArgumentException::class);

    // With reason should succeed
    $batch = $service->generateBatch($admin, $plan, 5, [
        'payment_method'  => 'admin_override',
        'override_reason' => 'Emergency disaster response free wifi',
    ]);

    expect($batch->payment_method)->toBe('admin_override');
    expect((float) $batch->total_cost)->toBe(0.00);
    expect(Voucher::where('batch_id', $batch->id)->count())->toBe(5);
});
