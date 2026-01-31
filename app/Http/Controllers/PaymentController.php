<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Plan;
use App\Models\User;
use Unicodeveloper\Paystack\Facades\Paystack;

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

        $amount = (int) round($plan->price * 100); // kobo

        try {
            $paymentInit = Paystack::getAuthorizationUrl([
                'amount' => $amount,
                'email' => $user->email,
                'metadata' => [
                    'plan_id' => $plan->id,
                ],
            ]);

            return $paymentInit->redirectNow();
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to initialize payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle Paystack callback
     */
    public function handleGatewayCallback()
    {
        try {
            $paymentDetails = Paystack::getPaymentData();
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Payment verification failed.');
        }

        if (empty($paymentDetails['status']) || ! $paymentDetails['status']) {
            return redirect()->route('dashboard')->with('error', 'Payment was not successful.');
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

        // Activate the plan: reset usage and set expiry
        $user->plan_id = $plan->id;
        $user->data_used = 0;
        $user->plan_expiry = now()->addDays($plan->validity_days ?? 0);
        $user->save(); // triggers observer -> RADIUS sync

        return redirect()->route('dashboard')->with('success', "Payment successful — you are now subscribed to {$plan->name}.");
    }
}
