<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Number;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\PendingSubscription;
use App\Models\RadCheck;
use App\Models\RadUserGroup;
use App\Models\Voucher;
use App\Models\AppSetting;
use App\Services\SubscriptionService;
use App\Services\RouterOwnerNotificationService;

class PaymentController extends Controller
{
    /**
     * Redirect the user to the configured payment gateway (Flutterwave or Paystack).
     */
    public function redirectToGateway(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'gateway' => ['nullable', 'string', 'in:flutterwave,paystack'],
        ]);

        $plan = Plan::findOrFail($request->input('plan_id'));

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to purchase a plan.');
        }

        $customerEmail = $this->resolveCustomerEmail($user);
        if (empty($customerEmail)) {
            return back()->with('error', 'Please add an email or phone number to your profile before purchasing.');
        }

        $gateway = $this->resolveGateway($request);
        $fromApp = str_starts_with(request()->getHost(), 'app.');

        $metadata = [
            'plan_id'  => $plan->id,
            'user_id'  => $user->id,
            'from_app' => $fromApp,
            'type'     => 'plan_subscription',
        ];

        if ($gateway === 'flutterwave') {
            return $this->initiateFlutterwavePayment(
                user: $user,
                amountInNaira: (float) $plan->price,
                email: $customerEmail,
                description: "Subscription for {$plan->name}",
                metadata: $metadata
            );
        }

        // Default: Paystack
        $amountInKobo = (int) round($plan->price * 100);
        return $this->initiatePaystackPayment(
            user: $user,
            amountInKobo: $amountInKobo,
            email: $customerEmail,
            metadata: $metadata
        );
    }

    /**
     * Initialize payment to fund user's prepaid wallet.
     */
    public function topUpWallet(Request $request)
    {
        $request->validate([
            'amount'  => ['required', 'numeric', 'min:500', 'max:500000'],
            'gateway' => ['nullable', 'string', 'in:flutterwave,paystack'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to top up your wallet.');
        }

        $customerEmail = $this->resolveCustomerEmail($user);
        if (empty($customerEmail)) {
            return back()->with('error', 'Please add an email or phone number to your profile before topping up.');
        }

        $amountInNaira = (float) $request->input('amount');
        $gateway = $this->resolveGateway($request);
        $fromApp = str_starts_with(request()->getHost(), 'app.');

        $metadata = [
            'type'         => 'wallet_topup',
            'user_id'      => $user->id,
            'amount_naira' => $amountInNaira,
            'from_app'     => $fromApp,
        ];

        if ($gateway === 'flutterwave') {
            return $this->initiateFlutterwavePayment(
                user: $user,
                amountInNaira: $amountInNaira,
                email: $customerEmail,
                description: 'HiFastLink Wallet Top-up',
                metadata: $metadata
            );
        }

        // Default: Paystack
        $amountInKobo = (int) round($amountInNaira * 100);
        return $this->initiatePaystackPayment(
            user: $user,
            amountInKobo: $amountInKobo,
            email: $customerEmail,
            metadata: $metadata
        );
    }

    /**
     * Handle payment gateway redirect callback (Flutterwave or Paystack).
     */
    public function handleGatewayCallback(Request $request)
    {
        $afterRoute = str_starts_with(request()->getHost(), 'app.') ? 'app.home' : 'dashboard';

        // Detect gateway
        $gateway = $request->query('gateway');
        if (! $gateway) {
            if ($request->has('transaction_id') || str_starts_with($request->query('tx_ref', ''), 'FLW_') || str_starts_with($request->query('reference', ''), 'FLW_')) {
                $gateway = 'flutterwave';
            } else {
                $gateway = 'paystack';
            }
        }

        if ($gateway === 'flutterwave') {
            return $this->handleFlutterwaveCallback($request, $afterRoute);
        }

        return $this->handlePaystackCallback($request, $afterRoute);
    }

    /**
     * Handle Flutterwave redirect callback.
     */
    protected function handleFlutterwaveCallback(Request $request, string $afterRoute)
    {
        $status = $request->query('status');
        if ($status === 'cancelled') {
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Payment was cancelled.');
        }

        $txId = $request->query('transaction_id');
        $txRef = $request->query('tx_ref') ?? $request->query('reference');

        if (! $txId && ! $txRef) {
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Missing Flutterwave payment reference.');
        }

        $baseUrl = rtrim(AppSetting::get('flw_base_url') ?: config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');
        $secretKey = AppSetting::get('flw_secret_key') ?: config('services.flutterwave.secret_key');

        if ($txId) {
            $verifyUrl = "{$baseUrl}/transactions/" . urlencode($txId) . "/verify";
        } else {
            $verifyUrl = "{$baseUrl}/transactions/verify_by_reference?tx_ref=" . urlencode($txRef);
        }

        try {
            $response = Http::withToken($secretKey)->timeout(20)->get($verifyUrl);
        } catch (\Throwable $e) {
            Log::error('Flutterwave verification connection error', [
                'error' => $e->getMessage(),
                'tx_id' => $txId,
                'tx_ref' => $txRef,
            ]);
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Unable to verify payment (network error).');
        }

        if (! $response->successful()) {
            Log::error('Flutterwave verify returned non-200', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Unable to verify payment with Flutterwave.');
        }

        $body = $response->json();
        $data = $body['data'] ?? [];

        if (($body['status'] ?? '') !== 'success' || ($data['status'] ?? '') !== 'successful') {
            Log::warning('Flutterwave payment not successful', ['response' => $body]);
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Payment verification failed or payment was unsuccessful.');
        }

        $reference = $data['tx_ref'] ?? $txRef;
        $amountInNaira = (float) ($data['amount'] ?? 0);
        $metadata = $data['meta'] ?? [];
        $customer = $data['customer'] ?? [];

        $fromApp = (bool) ($metadata['from_app'] ?? str_starts_with(request()->getHost(), 'app.'));
        $afterRoute = $fromApp ? 'app.home' : 'dashboard';

        return $this->fulfillPaymentAndRespond(
            reference: $reference,
            amountInNaira: $amountInNaira,
            gateway: 'flutterwave',
            metadata: $metadata,
            customer: $customer,
            afterRoute: $afterRoute
        );
    }

    /**
     * Handle Paystack redirect callback.
     */
    protected function handlePaystackCallback(Request $request, string $afterRoute)
    {
        $reference = $request->query('reference');
        if (! $reference) {
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Missing Paystack payment reference.');
        }

        $secretKey = AppSetting::get('paystack_secret_key') ?: config('services.paystack.secret_key');
        $paymentUrl = rtrim(AppSetting::get('paystack_payment_url') ?: config('services.paystack.payment_url', 'https://api.paystack.co'), '/');

        try {
            $response = Http::withToken($secretKey)
                ->timeout(20)
                ->get($paymentUrl . '/transaction/verify/' . urlencode($reference));
        } catch (\Throwable $e) {
            Log::error('Paystack verification connection error', [
                'error'     => $e->getMessage(),
                'reference' => $reference,
            ]);
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Unable to verify payment (network error).');
        }

        if (! $response->successful()) {
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Unable to verify payment (network error).');
        }

        $paymentDetails = $response->json();

        if (! isset($paymentDetails['status']) || ! $paymentDetails['status'] || ($paymentDetails['data']['status'] ?? '') !== 'success') {
            return $this->routeOrHome($afterRoute, Auth::user())->with('error', 'Payment verification failed.');
        }

        $data = $paymentDetails['data'] ?? [];
        $metadata = $data['metadata'] ?? [];
        $customer = $data['customer'] ?? [];
        $amountInNaira = ((float) ($data['amount'] ?? 0)) / 100; // Convert Kobo to Naira

        $fromApp = (bool) ($metadata['from_app'] ?? str_starts_with(request()->getHost(), 'app.'));
        $afterRoute = $fromApp ? 'app.home' : 'dashboard';

        return $this->fulfillPaymentAndRespond(
            reference: $data['reference'] ?? $reference,
            amountInNaira: $amountInNaira,
            gateway: 'paystack',
            metadata: $metadata,
            customer: $customer,
            afterRoute: $afterRoute
        );
    }

    /**
     * Handle webhooks from payment gateways (Flutterwave or Paystack).
     */
    public function handleWebhook(Request $request)
    {
        // ── 1. Check Flutterwave Webhook ────────────────────────────────────────
        $flwSecretHash = AppSetting::get('flw_secret_hash') ?: config('services.flutterwave.secret_hash');
        $incomingFlwHash = $request->header('verif-hash');

        if ($flwSecretHash && $incomingFlwHash && hash_equals($flwSecretHash, $incomingFlwHash)) {
            $event = $request->input('event');
            $data = $request->input('data');

            if ($event === 'charge.completed' && ($data['status'] ?? '') === 'successful') {
                $transactionId = $data['id'] ?? null;
                if ($transactionId) {
                    // Re-verify with Flutterwave API for maximum security
                    $baseUrl = rtrim(AppSetting::get('flw_base_url') ?: config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');
                    $secretKey = AppSetting::get('flw_secret_key') ?: config('services.flutterwave.secret_key');
                    try {
                        $verifyRes = Http::withToken($secretKey)->timeout(15)->get("{$baseUrl}/transactions/{$transactionId}/verify");
                        if ($verifyRes->successful() && ($verifyRes->json('data.status') ?? '') === 'successful') {
                            $verified = $verifyRes->json('data');
                            $this->executeFulfillment(
                                reference: $verified['tx_ref'],
                                amountInNaira: (float) $verified['amount'],
                                gateway: 'flutterwave',
                                metadata: $verified['meta'] ?? [],
                                customer: $verified['customer'] ?? []
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::error('Flutterwave webhook verification failed', ['error' => $e->getMessage()]);
                    }
                }
            }
            return response()->json(['status' => 'success'], 200);
        }

        // ── 2. Check Paystack Webhook ───────────────────────────────────────────
        $paystackSecret = AppSetting::get('paystack_secret_key') ?: config('services.paystack.secret_key');
        $paystackSig = $request->header('x-paystack-signature');

        if ($paystackSecret && $paystackSig && hash_equals(hash_hmac('sha512', $request->getContent(), $paystackSecret), $paystackSig)) {
            $event = $request->input('event');
            $data = $request->input('data');

            if ($event === 'charge.success' && ($data['status'] ?? '') === 'success') {
                $this->executeFulfillment(
                    reference: $data['reference'],
                    amountInNaira: ((float) ($data['amount'] ?? 0)) / 100,
                    gateway: 'paystack',
                    metadata: $data['metadata'] ?? [],
                    customer: $data['customer'] ?? []
                );
            }
            return response()->json(['status' => 'success'], 200);
        }

        return response()->json(['status' => 'invalid_signature'], 400);
    }

    /**
     * Unified payment fulfillment with HTTP redirect response.
     */
    protected function fulfillPaymentAndRespond(
        string $reference,
        float $amountInNaira,
        string $gateway,
        array $metadata,
        array $customer,
        string $afterRoute
    ) {
        $result = $this->executeFulfillment(
            reference: $reference,
            amountInNaira: $amountInNaira,
            gateway: $gateway,
            metadata: $metadata,
            customer: $customer
        );

        if (! $result['success']) {
            return $this->routeOrHome($afterRoute, $result['user'] ?? null)->with('error', $result['message']);
        }

        $user = $result['user'];

        // If user was newly resolved / retrieved, keep them logged in
        if ($user && ! Auth::check()) {
            Auth::login($user, remember: true);
        }

        // Handle wallet top-up response
        if (($result['type'] ?? '') === 'wallet_topup') {
            $topUpRoute = Route::has('vouchers.index') ? 'vouchers.index' : $afterRoute;
            return $this->routeOrHome($topUpRoute, $user)->with('success', $result['message']);
        }

        // Handle queued plan response
        if (! empty($result['queued'])) {
            return $this->routeOrHome($afterRoute, $user)->with('success', $result['message']);
        }

        // If user came from captive portal, bridge to MikroTik router
        if ($user && ($bridge = $this->buildCaptiveBridge($user))) {
            return $bridge;
        }

        return $this->routeOrHome($afterRoute, $user)->with('success', $result['message']);
    }

    /**
     * Resilient redirect to route or user home URL.
     */
    protected function routeOrHome(?string $routeName, ?User $user = null)
    {
        if ($routeName && Route::has($routeName)) {
            return redirect()->route($routeName);
        }
        if ($user) {
            return redirect()->to($user->homeUrl());
        }
        return redirect('/');
    }

    /**
     * Core idempotent payment fulfillment logic.
     */
    public function executeFulfillment(
        string $reference,
        float $amountInNaira,
        string $gateway,
        array $metadata,
        array $customer
    ): array {
        $user = Auth::user();

        // Fallback chain when session is lost during gateway redirect
        if (! $user && ! empty($metadata['user_id'])) {
            $user = User::find($metadata['user_id']);
        }

        if (! $user && ! empty($customer['email'])) {
            $email = $customer['email'];
            $user = User::where('email', $email)->first();
            // Phone-only users: generated email is {digits}@hifastlink.ng
            if (! $user && str_ends_with($email, '@hifastlink.ng')) {
                $phone = '+' . str_replace('@hifastlink.ng', '', $email);
                $user = User::where('phone', $phone)->first();
            }
        }

        if (! $user && ! empty($customer['phonenumber'])) {
            $phone = $customer['phonenumber'];
            $user = User::where('phone', $phone)->orWhere('phone', '+' . ltrim($phone, '+'))->first();
        }

        if (! $user) {
            return [
                'success' => false,
                'message' => 'User not found for this payment.',
                'user'    => null,
            ];
        }

        $routerId = $user->router_id ?? null;

        // ── 1. Handle Wallet Top-Up ───────────────────────────────────────────
        if (($metadata['type'] ?? '') === 'wallet_topup') {
            if (Transaction::where('reference', $reference)->exists()) {
                return [
                    'success' => true,
                    'message' => 'Your wallet top-up was already processed.',
                    'type'    => 'wallet_topup',
                    'user'    => $user,
                ];
            }

            DB::transaction(function () use ($user, $amountInNaira, $reference, $gateway, $routerId) {
                $fresh = User::lockForUpdate()->find($user->id);
                $fresh->increment('wallet_balance', $amountInNaira);

                Transaction::create([
                    'user_id'     => $fresh->id,
                    'plan_id'     => null,
                    'amount'      => $amountInNaira,
                    'reference'   => $reference,
                    'type'        => 'wallet_topup',
                    'description' => 'Wallet top-up via ' . ucfirst($gateway),
                    'status'      => 'completed',
                    'gateway'     => $gateway,
                    'paid_at'     => now(),
                    'router_id'   => $routerId,
                ]);
            });

            return [
                'success' => true,
                'message' => "Wallet topped up successfully with ₦" . number_format($amountInNaira, 2) . "!",
                'type'    => 'wallet_topup',
                'user'    => $user,
            ];
        }

        // ── 2. Handle Plan Subscription ───────────────────────────────────────
        $planId = $metadata['plan_id'] ?? null;
        $plan = Plan::find($planId);
        if (! $plan) {
            return [
                'success' => false,
                'message' => 'Plan not found for this payment.',
                'user'    => $user,
            ];
        }

        // Idempotency guard: skip if already processed
        if (Transaction::where('reference', $reference)->exists()) {
            return [
                'success' => true,
                'message' => 'Your payment was already processed.',
                'type'    => 'plan_subscription',
                'user'    => $user,
            ];
        }

        // Check if user has active plan with data left
        $hasActivePlan = $user->plan_expiry && $user->plan_expiry->isFuture();
        $hasDataLeft = ! is_null($user->data_limit) && $user->data_used < $user->data_limit;

        // If the current plan has no data remaining, expire it so the new plan can activate immediately
        if ($hasActivePlan && ($user->remaining_data ?? 0) <= 0) {
            $user->plan_id = null;
            $user->plan_expiry = null;
            $user->save(); // triggers PlanSyncService

            $hasActivePlan = false;
            $hasDataLeft = false;
        }

        if ($hasActivePlan && $hasDataLeft) {
            // Queue the plan
            PendingSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            // Record Payment
            Payment::create([
                'user_id'   => $user->id,
                'reference' => $reference,
                'amount'    => $amountInNaira,
                'plan_name' => $plan->name,
                'router_id' => $routerId,
            ]);

            // Record Transaction
            Transaction::firstOrCreate(
                ['reference' => $reference],
                [
                    'user_id'   => $user->id,
                    'plan_id'   => $plan->id,
                    'amount'    => $amountInNaira,
                    'status'    => 'completed',
                    'gateway'   => $gateway,
                    'paid_at'   => now(),
                    'router_id' => $routerId,
                ]
            );

            try {
                Cache::forget('user:current_plan:' . $user->id);
            } catch (\Throwable $e) {
                // ignore cache failures
            }

            return [
                'success' => true,
                'message' => "Plan queued! It will start when your current plan expires.",
                'queued'  => true,
                'type'    => 'plan_subscription',
                'user'    => $user,
            ];
        }

        // Immediate activation with rollover
        $subscriptionService = new SubscriptionService();
        $rolloverData = $subscriptionService->consumeRolloverOnPurchase($user, $plan);

        if (empty($rolloverData)) {
            $rolloverData = $user->calculateRolloverFor($plan);
        }

        // Convert plan limit to bytes (support GB/MB/Unlimited)
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } elseif ($plan->limit_unit === 'GB') {
            $planBytes = (int) ($plan->data_limit * 1073741824);
        } else {
            $planBytes = (int) ($plan->data_limit * 1048576);
        }

        $user->plan_id = $plan->id;
        $user->data_used = 0;
        $user->data_limit = $planBytes === null ? null : ($planBytes + ($rolloverData ?? 0));
        $user->plan_expiry = now()->addDays($plan->validity_days ?? 0);
        $user->plan_started_at = now();
        $user->connection_status = 'active';

        if ($plan->is_family) {
            $user->is_family_admin = true;
            $user->parent_id = null;
            User::where('parent_id', $user->id)->update(['parent_id' => null]);
        } else {
            $user->is_family_admin = false;
        }

        $user->family_limit = $plan->family_limit ?? 0;
        $user->save(); // triggers observer -> RADIUS sync

        // Clean up vouchers from previous plan
        $this->cleanupOldVouchers($user);

        // Record the Payment
        Payment::create([
            'user_id'   => $user->id,
            'reference' => $reference,
            'amount'    => $amountInNaira,
            'plan_name' => $plan->name,
            'router_id' => $routerId,
        ]);

        // Record Transaction
        $transaction = Transaction::firstOrCreate(
            ['reference' => $reference],
            [
                'user_id'   => $user->id,
                'plan_id'   => $plan->id,
                'amount'    => $amountInNaira,
                'status'    => 'completed',
                'gateway'   => $gateway,
                'paid_at'   => now(),
                'router_id' => $routerId,
            ]
        );
        Log::info("Transaction recorded for payment {$reference} with ID: {$transaction->id} via {$gateway}");

        // Clear cached plan
        try {
            Cache::forget('user:current_plan:' . $user->id);
        } catch (\Throwable $e) {
            // ignore
        }

        // Notify router owner of new plan subscription
        if ($user->router_id) {
            try {
                $userRouter = \App\Models\Router::find($user->router_id);
                if ($userRouter?->owner_id) {
                    app(RouterOwnerNotificationService::class)
                        ->notifyNewPlanSubscription($user, $userRouter, $plan);
                }
            } catch (\Throwable $e) {
                Log::warning('Router owner notification failed: ' . $e->getMessage());
            }
        }

        $rolloverMessage = $rolloverData > 0 ? " with " . Number::fileSize($rolloverData) . " rollover data!" : "!";

        return [
            'success' => true,
            'message' => "Payment successful — you are now subscribed to {$plan->name}{$rolloverMessage}",
            'queued'  => false,
            'type'    => 'plan_subscription',
            'user'    => $user,
        ];
    }

    /**
     * Initiate Flutterwave Standard Checkout payment.
     */
    protected function initiateFlutterwavePayment(User $user, float $amountInNaira, string $email, string $description, array $metadata)
    {
        $reference = 'FLW_' . Str::random(12);
        $secretKey = AppSetting::get('flw_secret_key') ?: config('services.flutterwave.secret_key');
        $baseUrl = rtrim(AppSetting::get('flw_base_url') ?: config('services.flutterwave.base_url', 'https://api.flutterwave.com/v3'), '/');

        if (empty($secretKey)) {
            Log::error('Flutterwave secret key is not configured.');
            return back()->with('error', 'Payment gateway configuration error.');
        }

        $payload = [
            'tx_ref'         => $reference,
            'amount'         => $amountInNaira,
            'currency'       => 'NGN',
            'redirect_url'   => route('payment.callback', ['gateway' => 'flutterwave']),
            'meta'           => $metadata,
            'customer'       => [
                'email'       => $email,
                'phonenumber' => $user->phone ?? '',
                'name'        => $user->name,
            ],
            'customizations' => [
                'title'       => config('app.name', 'HiFastLink'),
                'description' => $description,
            ],
        ];

        try {
            $response = Http::withToken($secretKey)
                ->timeout(20)
                ->post("{$baseUrl}/payments", $payload);

            Log::info('Flutterwave API payment initialize response', [
                'status'     => $response->status(),
                'successful' => $response->successful(),
                'reference'  => $reference,
            ]);
        } catch (\Throwable $e) {
            Log::error('Flutterwave API call failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
            return back()->with('error', 'Network error: Unable to connect to Flutterwave payment gateway.');
        }

        if (! $response->successful()) {
            $apiError = $response->json('message') ?? 'Unable to initialize Flutterwave payment.';
            return back()->with('error', $apiError);
        }

        $body = $response->json();
        $paymentLink = $body['data']['link'] ?? null;

        if (($body['status'] ?? '') !== 'success' || empty($paymentLink)) {
            $message = $body['message'] ?? 'Unable to initialize payment link.';
            return back()->with('error', $message);
        }

        return redirect($paymentLink);
    }

    /**
     * Initiate Paystack Standard Checkout payment.
     */
    protected function initiatePaystackPayment(User $user, int $amountInKobo, string $email, array $metadata)
    {
        $reference = 'PaystackRef_' . Str::random(12);
        $secretKey = AppSetting::get('paystack_secret_key') ?: config('services.paystack.secret_key');
        $paymentUrl = rtrim(AppSetting::get('paystack_payment_url') ?: config('services.paystack.payment_url', 'https://api.paystack.co'), '/');

        $payload = [
            'email'        => $email,
            'amount'       => $amountInKobo,
            'reference'    => $reference,
            'callback_url' => route('payment.callback', ['gateway' => 'paystack']),
            'metadata'     => $metadata,
        ];

        try {
            $response = Http::withToken($secretKey)
                ->timeout(20)
                ->post($paymentUrl . '/transaction/initialize', $payload);

            Log::info('Paystack API response', [
                'status'     => $response->status(),
                'successful' => $response->successful(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Paystack API call failed', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
            return back()->with('error', 'Network error: Unable to connect to payment gateway.');
        }

        if (! $response->successful()) {
            $apiError = $response->json('message') ?? 'Unable to initialize payment (gateway error).';
            return back()->with('error', $apiError);
        }

        $body = $response->json();
        if (! isset($body['status']) || ! $body['status'] || empty($body['data']['authorization_url'])) {
            $message = $body['message'] ?? 'Unable to initialize payment.';
            return back()->with('error', $message);
        }

        return redirect($body['data']['authorization_url']);
    }

    /**
     * Resolve default or requested gateway.
     */
    protected function resolveGateway(Request $request): string
    {
        $requested = strtolower(trim((string) $request->input('gateway', '')));
        if (in_array($requested, ['flutterwave', 'paystack'])) {
            return $requested;
        }

        $configured = strtolower((string) (AppSetting::get('payment_gateway') ?: config('services.payment_gateway', env('PAYMENT_GATEWAY', 'flutterwave'))));
        return in_array($configured, ['flutterwave', 'paystack']) ? $configured : 'flutterwave';
    }

    /**
     * Resolve valid customer email.
     */
    protected function resolveCustomerEmail(User $user): ?string
    {
        $email = $user->email;
        if (empty($email) && $user->phone) {
            $digits = preg_replace('/\D/', '', $user->phone);
            $email = $digits . '@hifastlink.ng';
        }
        return $email ?: null;
    }

    /**
     * Build captive portal router bridge view if user was redirected from hotspot.
     */
    private function buildCaptiveBridge($user)
    {
        $linkLogin = session()->pull('captive_link_login');

        if (! $linkLogin) {
            return null;
        }

        $mac    = session()->pull('captive_mac');
        $ip     = session()->pull('captive_ip');
        $router = session()->pull('captive_router');

        $rad = RadCheck::where('username', $user->username)
            ->where('attribute', 'Cleartext-Password')
            ->first();
        $password = $rad?->value ?? $user->radius_password;

        if (! $password) {
            return null;
        }

        return response()->view('hotspot.redirect_to_router', [
            'username'   => $user->username,
            'password'   => $password,
            'link_login' => $linkLogin,
            'link_orig'  => route('app.home'),
            'mac'        => $mac,
            'ip'         => $ip,
            'router'     => $router,
        ]);
    }

    /**
     * Clean up expired or stale vouchers.
     */
    private function cleanupOldVouchers($user): void
    {
        $stale = Voucher::where('created_by', $user->id)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('expires_at')->where('expires_at', '<', now());
                })->orWhere(function ($q3) {
                    $q3->whereColumn('used_count', '>=', 'max_uses')
                       ->where('used_at', '<', now()->subDays(7));
                });
            })
            ->get();

        foreach ($stale as $v) {
            RadCheck::where('username', $v->code)->delete();
            \App\Models\RadReply::where('username', $v->code)->delete();
            $v->delete();
        }

        if ($stale->isNotEmpty()) {
            Log::info("Cleaned up {$stale->count()} stale voucher(s) for {$user->username} after plan activation.");
        }
    }
}
