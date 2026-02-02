<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Number;
use App\Models\RadCheck;
use App\Models\RadUserGroup;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Redirect the user to Paystack payment gateway
     */
    public function redirectToGateway(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->input('plan_id'));

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'You must be logged in to purchase a plan.');
        }

        $amount = (int) round($plan->price * 100); // amount in Kobo

        $reference = 'PaystackRef_' . Str::random(12);
        $payload = [
            'email' => $user->email,
            'amount' => $amount,
            'reference' => $reference,
            'callback_url' => route('payment.callback'),
            'metadata' => [
                'plan_id' => $plan->id,
            ],
        ];

        try {
            $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
                ->post(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/initialize', $payload);

            \Log::info('Paystack API response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'successful' => $response->successful(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Paystack API call failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return back()->with('error', 'Network error: Unable to connect to payment gateway.');
        }

        // dd($response->json()); // --- DEBUG ---

        if (! $response->successful()) {
            return back()->with('error', 'Unable to initialize payment (network error).');
        }

        $body = $response->json();
        if (! isset($body['status']) || ! $body['status'] || empty($body['data']['authorization_url'])) {
            $message = $body['message'] ?? 'Unable to initialize payment.';
            return back()->with('error', $message);
        }

        return redirect($body['data']['authorization_url']);
    }

    /**
     * Handle Paystack callback
     */
    public function handleGatewayCallback(Request $request)
    {
        $reference = request()->query('reference');

        if (! $reference) {
            return redirect()->route('dashboard')->with('error', 'Missing payment reference.');
        }

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->get(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/verify/' . urlencode($reference));

        if (! $response->successful()) {
            return redirect()->route('dashboard')->with('error', 'Unable to verify payment (network error).');
        }

        $paymentDetails = $response->json();

        if (! isset($paymentDetails['status']) || ! $paymentDetails['status'] || ($paymentDetails['data']['status'] ?? '') !== 'success') {
            return redirect()->route('dashboard')->with('error', 'Payment verification failed.');
        }

        $data = $paymentDetails['data'] ?? [];
        $metadata = $data['metadata'] ?? [];
        $planId = $metadata['plan_id'] ?? null;

        $user = Auth::user();
        // Fallback: try to resolve by customer email if not authenticated
        if (! $user && ! empty($data['customer']['email'])) {
            $user = User::where('email', $data['customer']['email'])->first();
        }

        if (! $user) {
            return redirect()->route('dashboard')->with('error', 'User not found for this payment.');
        }

        $plan = Plan::find($planId);
        if (! $plan) {
            return redirect()->route('dashboard')->with('error', 'Plan not found for this payment.');
        }

        // Check if user has active plan with data left
        $hasActivePlan = $user->plan_expiry && $user->plan_expiry->isFuture();
        $hasDataLeft = $user->data_used < $user->data_limit;

        if ($hasActivePlan && $hasDataLeft) {
            // Queue the plan
            \App\Models\PendingSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            // Record the payment even for queued plans
            Payment::create([
                'user_id' => $user->id,
                'reference' => $data['reference'],
                'amount' => $data['amount'] / 100, // Convert Kobo to Naira
                'plan_name' => $plan->name,
            ]);

            // Also create transaction record
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $data['amount'] / 100,
                'reference' => $data['reference'],
                'status' => 'success',
                'gateway' => 'paystack',
                'paid_at' => now(),
            ]);

            // RADIUS sync logic
            try {
                RadCheck::updateOrCreate(
                    ['username' => $user->username],
                    [
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $user->radius_password ?? 'default_password',
                    ]
                );

                // This assumes your $plan object has a 'name' like "Daily-100MB"
                if (isset($plan) && $plan) {
                    // Ensure the Group Model is imported at the top!
                    RadUserGroup::updateOrCreate(
                        ['username' => $user->username],
                        [
                            'groupname' => $plan->name, // e.g., "Daily-100MB"
                            'priority'  => 10
                        ]
                    );
                }
                Log::info("RadCheck created/updated for user {$user->username}");
            } catch (\Exception $e) {
                Log::error("Failed to create/update RadCheck for user {$user->username}: " . $e->getMessage());
            }

            try {
                RadUserGroup::updateOrCreate(
                    ['username' => $user->username],
                    [
                        'groupname' => 'default_group',
                    ]
                );
                Log::info("RadUserGroup created/updated for user {$user->username}");
            } catch (\Exception $e) {
                Log::error("Failed to create/update RadUserGroup for user {$user->username}: " . $e->getMessage());
            }

            return redirect()->route('dashboard')->with('success', "Plan queued! It will start when your current plan expires.");
        } else {
            // Activate immediately with rollover
            $rolloverData = $user->calculateRolloverFor($plan);

            $user->plan_id = $plan->id;
            $user->data_used = 0;
            $user->data_limit = $plan->data_limit + $rolloverData;
            $user->plan_expiry = now()->addDays($plan->validity_days ?? 0);
            $user->plan_started_at = now();
            $user->is_family_admin = $plan->is_family;
            $user->family_limit = $plan->family_limit;
            $user->save(); // triggers observer -> RADIUS sync

            // Record the payment
            Payment::create([
                'user_id' => $user->id,
                'reference' => $data['reference'],
                'amount' => $data['amount'] / 100, // Convert Kobo to Naira
                'plan_name' => $plan->name,
            ]);

            // Also create transaction record
            try {
                \Illuminate\Support\Facades\Log::info("Attempting to create transaction for payment {$data['reference']}", [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => $data['amount'] / 100,
                    'reference' => $data['reference'],
                    'status' => 'success',
                    'gateway' => 'paystack',
                    'paid_at' => now(),
                ]);

                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => $data['amount'] / 100,
                    'reference' => $data['reference'],
                    'status' => 'success',
                    'gateway' => 'paystack',
                    'paid_at' => now(),
                ]);

                \Illuminate\Support\Facades\Log::info("Transaction created successfully for payment {$data['reference']} with ID: {$transaction->id}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to create transaction for payment {$data['reference']}: " . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'plan_exists' => \App\Models\Plan::find($plan->id) ? 'yes' : 'no',
                    'user_exists' => \App\Models\User::find($user->id) ? 'yes' : 'no',
                ]);
            }

            // RADIUS sync logic
            try {
                RadCheck::updateOrCreate(
                    ['username' => $user->username],
                    [
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $user->radius_password ?? 'default_password',
                    ]
                );
                Log::info("RadCheck created/updated for user {$user->username}");
            } catch (\Exception $e) {
                Log::error("Failed to create/update RadCheck for user {$user->username}: " . $e->getMessage());
            }

            try {
                RadUserGroup::updateOrCreate(
                    ['username' => $user->username],
                    [
                        'groupname' => 'default_group',
                    ]
                );
                Log::info("RadUserGroup created/updated for user {$user->username}");
            } catch (\Exception $e) {
                Log::error("Failed to create/update RadUserGroup for user {$user->username}: " . $e->getMessage());
            }

            $rolloverMessage = $rolloverData > 0 ? " with " . Number::fileSize($rolloverData) . " rollover data!" : "!";
            return redirect()->route('dashboard')->with('success', "Payment successful — you are now subscribed to {$plan->name}{$rolloverMessage}");
        }
    }
}
