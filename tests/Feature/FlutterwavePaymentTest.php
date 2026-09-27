<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\PendingSubscription;
use App\Models\AppSetting;

class FlutterwavePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure radcheck, radreply, radgroupreply, radusergroup exist in sqlite memory if touched
        if (! Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op')->default(':=');
                $table->string('value');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op')->default(':=');
                $table->string('value');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('radgroupreply')) {
            Schema::create('radgroupreply', function (Blueprint $table) {
                $table->id();
                $table->string('groupname');
                $table->string('attribute');
                $table->string('op')->default(':=');
                $table->string('value');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('radusergroup')) {
            Schema::create('radusergroup', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(1);
                $table->timestamps();
            });
        }

        config([
            'services.payment_gateway' => 'flutterwave',
            'services.flutterwave.public_key' => 'FLWPUBK_TEST-12345',
            'services.flutterwave.secret_key' => 'FLWSECK_TEST-12345',
            'services.flutterwave.secret_hash' => 'test_flw_secret_hash',
            'services.flutterwave.base_url' => 'https://api.flutterwave.com/v3',
        ]);
    }

    public function test_redirects_to_flutterwave_on_plan_purchase()
    {
        Http::fake([
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'message' => 'Hosted Link',
                'data' => [
                    'link' => 'https://ravemodal-dev.herokuapp.com/v3/hosted/pay/test12345',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'phone' => '+2348012345678',
        ]);

        $plan = Plan::create([
            'name' => 'Monthly Standard',
            'price' => 5000,
            'data_limit' => 50,
            'limit_unit' => 'GB',
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('pay'), [
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect('https://ravemodal-dev.herokuapp.com/v3/hosted/pay/test12345');

        Http::assertSent(function ($request) use ($plan, $user) {
            return $request->url() === 'https://api.flutterwave.com/v3/payments' &&
                $request['amount'] == 5000 &&
                $request['currency'] === 'NGN' &&
                $request['customer']['email'] === $user->email &&
                $request['meta']['plan_id'] === $plan->id &&
                $request['meta']['user_id'] === $user->id;
        });
    }

    public function test_redirects_to_flutterwave_on_wallet_topup()
    {
        Http::fake([
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'message' => 'Hosted Link',
                'data' => [
                    'link' => 'https://ravemodal-dev.herokuapp.com/v3/hosted/pay/wallet123',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'walletuser@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('wallet.topup'), [
            'amount' => 2000,
        ]);

        $response->assertRedirect('https://ravemodal-dev.herokuapp.com/v3/hosted/pay/wallet123');

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://api.flutterwave.com/v3/payments' &&
                $request['amount'] == 2000 &&
                $request['meta']['type'] === 'wallet_topup' &&
                $request['meta']['user_id'] === $user->id;
        });
    }

    public function test_handles_flutterwave_callback_and_activates_subscription()
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'plan_id' => null,
            'plan_expiry' => null,
            'data_limit' => null,
            'data_used' => 0,
        ]);

        $plan = Plan::create([
            'name' => 'Weekly Boost',
            'price' => 1500,
            'data_limit' => 10,
            'limit_unit' => 'GB',
            'validity_days' => 7,
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/998877/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 998877,
                    'tx_ref' => 'FLW_test_ref_123',
                    'amount' => 1500,
                    'currency' => 'NGN',
                    'status' => 'successful',
                    'meta' => [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                        'from_app' => false,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('payment.callback', [
            'gateway' => 'flutterwave',
            'status' => 'successful',
            'transaction_id' => '998877',
            'tx_ref' => 'FLW_test_ref_123',
        ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals($plan->id, $user->plan_id);
        $this->assertNotNull($user->plan_expiry);
        $this->assertEquals('active', $user->connection_status);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'FLW_test_ref_123',
            'gateway' => 'flutterwave',
            'status' => 'completed',
            'amount' => 1500,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'reference' => 'FLW_test_ref_123',
            'amount' => 1500,
            'user_id' => $user->id,
            'plan_name' => $plan->name,
        ]);
    }

    public function test_handles_flutterwave_callback_for_wallet_topup()
    {
        $user = User::factory()->create([
            'email' => 'topup@example.com',
            'wallet_balance' => 500,
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/554433/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 554433,
                    'tx_ref' => 'FLW_topup_ref_1',
                    'amount' => 3000,
                    'currency' => 'NGN',
                    'status' => 'successful',
                    'meta' => [
                        'type' => 'wallet_topup',
                        'user_id' => $user->id,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('payment.callback', [
            'gateway' => 'flutterwave',
            'status' => 'successful',
            'transaction_id' => '554433',
            'tx_ref' => 'FLW_topup_ref_1',
        ]));

        $response->assertRedirect(route('vouchers.index'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals(3500, $user->wallet_balance);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'FLW_topup_ref_1',
            'gateway' => 'flutterwave',
            'type' => 'wallet_topup',
            'status' => 'completed',
            'amount' => 3000,
            'user_id' => $user->id,
        ]);
    }

    public function test_queues_plan_when_user_has_active_plan_with_data()
    {
        $activePlan = Plan::create([
            'name' => 'Current Plan',
            'price' => 1000,
            'data_limit' => 20,
            'limit_unit' => 'GB',
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $newPlan = Plan::create([
            'name' => 'Next Plan',
            'price' => 2000,
            'data_limit' => 50,
            'limit_unit' => 'GB',
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'queued@example.com',
            'plan_id' => $activePlan->id,
            'plan_expiry' => now()->addDays(15),
            'data_limit' => 20 * 1073741824,
            'data_used' => 5 * 1073741824, // 15 GB remaining
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/112233/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 112233,
                    'tx_ref' => 'FLW_queued_ref',
                    'amount' => 2000,
                    'currency' => 'NGN',
                    'status' => 'successful',
                    'meta' => [
                        'plan_id' => $newPlan->id,
                        'user_id' => $user->id,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('payment.callback', [
            'gateway' => 'flutterwave',
            'status' => 'successful',
            'transaction_id' => '112233',
        ]));

        $response->assertSessionHas('success');

        $user->refresh();
        // Current plan should remain untouched
        $this->assertEquals($activePlan->id, $user->plan_id);

        // PendingSubscription record created
        $this->assertDatabaseHas('pending_subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'FLW_queued_ref',
            'gateway' => 'flutterwave',
            'status' => 'completed',
        ]);
    }

    public function test_flutterwave_callback_is_idempotent()
    {
        $user = User::factory()->create([
            'email' => 'idempotent@example.com',
        ]);

        $plan = Plan::create([
            'name' => 'Single Plan',
            'price' => 1000,
            'data_limit' => 5,
            'limit_unit' => 'GB',
            'validity_days' => 7,
            'is_active' => true,
        ]);

        // Pre-create the transaction record
        Transaction::create([
            'reference' => 'FLW_already_processed',
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'completed',
            'gateway' => 'flutterwave',
            'paid_at' => now(),
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/445566/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 445566,
                    'tx_ref' => 'FLW_already_processed',
                    'amount' => 1000,
                    'currency' => 'NGN',
                    'status' => 'successful',
                    'meta' => [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('payment.callback', [
            'gateway' => 'flutterwave',
            'status' => 'successful',
            'transaction_id' => '445566',
        ]));

        $response->assertSessionHas('success', 'Your payment was already processed.');

        // Only 1 transaction in DB
        $this->assertEquals(1, Transaction::where('reference', 'FLW_already_processed')->count());
    }

    public function test_processes_valid_flutterwave_webhook()
    {
        $user = User::factory()->create([
            'email' => 'webhookuser@example.com',
            'plan_id' => null,
        ]);

        $plan = Plan::create([
            'name' => 'Webhook Plan',
            'price' => 2500,
            'data_limit' => 15,
            'limit_unit' => 'GB',
            'validity_days' => 14,
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/778899/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 778899,
                    'tx_ref' => 'FLW_webhook_ref_1',
                    'amount' => 2500,
                    'currency' => 'NGN',
                    'status' => 'successful',
                    'meta' => [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'verif-hash' => 'test_flw_secret_hash',
        ])->postJson(route('payment.webhook'), [
            'event' => 'charge.completed',
            'data' => [
                'id' => 778899,
                'status' => 'successful',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertEquals($plan->id, $user->plan_id);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'FLW_webhook_ref_1',
            'gateway' => 'flutterwave',
            'status' => 'completed',
        ]);
    }

    public function test_rejects_webhook_with_invalid_signature()
    {
        $response = $this->withHeaders([
            'verif-hash' => 'wrong_hash',
        ])->postJson(route('payment.webhook'), [
            'event' => 'charge.completed',
            'data' => [
                'id' => 12345,
            ],
        ]);

        $response->assertStatus(400);
        $response->assertJson(['status' => 'invalid_signature']);
    }

    public function test_can_still_use_paystack_gateway_when_requested()
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/0123456789',
                    'access_code' => '0123456789',
                    'reference' => 'PaystackRef_test123',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'paystackuser@example.com',
        ]);

        $plan = Plan::create([
            'name' => 'Paystack Plan',
            'price' => 3000,
            'data_limit' => 30,
            'limit_unit' => 'GB',
            'validity_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('pay'), [
            'plan_id' => $plan->id,
            'gateway' => 'paystack',
        ]);

        $response->assertRedirect('https://checkout.paystack.com/0123456789');

        Http::assertSent(function ($request) use ($plan, $user) {
            return str_contains($request->url(), 'paystack.co/transaction/initialize') &&
                $request['amount'] == 300000 && // In Kobo
                $request['email'] === $user->email &&
                $request['metadata']['plan_id'] === $plan->id;
        });
    }

    public function test_handles_paystack_callback_and_activates_subscription()
    {
        $user = User::factory()->create([
            'email' => 'ps_customer@example.com',
            'plan_id' => null,
        ]);

        $plan = Plan::create([
            'name' => 'Paystack Boost',
            'price' => 2000,
            'data_limit' => 20,
            'limit_unit' => 'GB',
            'validity_days' => 15,
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/PaystackRef_verified_123' => Http::response([
                'status' => true,
                'data' => [
                    'reference' => 'PaystackRef_verified_123',
                    'amount' => 200000, // 2000 NGN in Kobo
                    'status' => 'success',
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'user_id' => $user->id,
                        'from_app' => false,
                    ],
                    'customer' => [
                        'email' => $user->email,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('payment.callback', [
            'reference' => 'PaystackRef_verified_123',
        ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals($plan->id, $user->plan_id);

        $this->assertDatabaseHas('transactions', [
            'reference' => 'PaystackRef_verified_123',
            'gateway' => 'paystack',
            'status' => 'completed',
            'amount' => 2000,
            'user_id' => $user->id,
        ]);
    }

    public function test_uses_dynamic_keys_from_app_settings()
    {
        AppSetting::set('flw_secret_key', 'FLWSECK_DYNAMIC_999');
        AppSetting::set('flw_base_url', 'https://custom-api.flutterwave.com/v3');

        Http::fake([
            'https://custom-api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'message' => 'Hosted Link',
                'data' => [
                    'link' => 'https://custom-api.flutterwave.com/v3/hosted/pay/test999',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'dynamic_flw@example.com',
        ]);

        $plan = Plan::create([
            'name' => 'Dynamic Plan',
            'price' => 1500,
            'data_limit' => 10,
            'limit_unit' => 'GB',
            'validity_days' => 7,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('pay'), [
            'plan_id' => $plan->id,
            'gateway' => 'flutterwave',
        ]);

        $response->assertRedirect('https://custom-api.flutterwave.com/v3/hosted/pay/test999');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://custom-api.flutterwave.com/v3/payments' &&
                $request->hasHeader('Authorization', 'Bearer FLWSECK_DYNAMIC_999');
        });
    }

    public function test_uses_dynamic_default_gateway_from_app_settings()
    {
        // When admin selects Paystack as default in UI, no need to specify gateway in request
        AppSetting::set('payment_gateway', 'paystack');
        AppSetting::set('paystack_secret_key', 'paystack_secret_mock_888');

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/paystack_auth_888',
                    'access_code' => '888',
                    'reference' => 'PaystackRef_888',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'email' => 'dynamic_default@example.com',
        ]);

        $plan = Plan::create([
            'name' => 'Dynamic Paystack Default',
            'price' => 2500,
            'data_limit' => 25,
            'limit_unit' => 'GB',
            'validity_days' => 14,
            'is_active' => true,
        ]);

        // Request without gateway parameter -> should resolve to paystack via AppSetting
        $response = $this->actingAs($user)->post(route('pay'), [
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect('https://checkout.paystack.com/paystack_auth_888');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'paystack.co/transaction/initialize') &&
                $request->hasHeader('Authorization', 'Bearer paystack_secret_mock_888');
        });
    }
}

