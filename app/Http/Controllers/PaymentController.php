<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Plan;
use App\Models\User;
use App\Models\Payment;use Illuminate\Support\Number;
class PaymentController extends Controller
{
    /**
     * Redirect the user to Paystack payment gateway
     */
    public function redirectToGateway(Request $request)
    {
        // --- DEBUG START ---
        // 1. Check if we are receiving the Plan ID
        // dd($request->all()); 

        // 2. See the response from Paystack (Comment out step 1 and uncomment this after)
        // --- DEBUG END ---
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

        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post(rtrim(env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'), '/') . '/transaction/initialize', $payload);

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
            $user->pending_plan_id = $plan->id;
            $user->pending_plan_purchased_at = now();
            $user->save();

            return redirect()->route('dashboard')->with('success', "Plan queued! It will start when your current plan expires.");
        } else {
            // Activate immediately with rollover
            $rolloverData = 0;
            if ($user->data_limit && $user->data_used < $user->data_limit) {
                $rolloverData = $user->data_limit - $user->data_used;
            }

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

            $rolloverMessage = $rolloverData > 0 ? " with " . Number::fileSize($rolloverData) . " rollover data!" : "!";
            return redirect()->route('dashboard')->with('success', "Payment successful — you are now subscribed to {$plan->name}{$rolloverMessage}");
        }
    }
}
