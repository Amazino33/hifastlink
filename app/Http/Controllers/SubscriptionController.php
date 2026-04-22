<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Number;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('subscriptions.plans', compact('plans'));
    }

    public function subscribe(Request $request, Plan $plan)
    {
        $user = Auth::user();

        // Check if user can afford the plan
        if ($user->wallet_balance < $plan->price) {
            return back()->with('error', 'Insufficient balance. Please top up your wallet.');
        }

        // Deduct from wallet
        $user->decrement('wallet_balance', $plan->price);

        // Calculate rollover from current plan if exists and has same validity
        $rolloverData = 0;
        if ($user->plan_id && $user->plan) {
            // Check if current plan has same validity days
            $currentPlanValidity = $user->plan->validity_days ?? 0;
            $newPlanValidity = $plan->validity_days ?? 0;
            
            if ($currentPlanValidity == $newPlanValidity) {
                // Calculate remaining data from current plan (in bytes)
                $rolloverData = max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));
            }
        }

        // Convert plan limit to bytes (support GB/MB/Unlimited) - same as PaymentController
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } elseif ($plan->limit_unit === 'GB') {
            $planBytes = (int) ($plan->data_limit * 1073741824);
        } else {
            // Default to MB
            $planBytes = (int) ($plan->data_limit * 1048576);
        }
        
        // Calculate new data limit with rollover
        $newDataLimit = $planBytes === null ? null : ($planBytes + $rolloverData);

        // Rollover time (extend if active, start from now if expired)
        $now = Carbon::now();
        if ($user->plan_expiry && $user->plan_expiry->gt($now)) {
            // Extend existing expiry
            $newExpiry = Carbon::parse($user->plan_expiry)->addDays($plan->validity_days ?? 0);
        } else {
            // Start new expiry from now
            $newExpiry = $now->copy()->addDays($plan->validity_days ?? 0);
        }

        // Update user subscription with proper values
        $user->update([
            'data_limit' => $newDataLimit,
            'data_used' => 0, // Reset usage for new plan
            'plan_expiry' => $newExpiry,
            'plan_id' => $plan->id,
            'connection_status' => 'active',
        ]);

        // Sync to RADIUS with new limits
        Artisan::call('radius:sync-users');

        $rolloverMessage = $rolloverData > 0 ? " with " . Number::fileSize($rolloverData) . " rollover data" : "";
        return redirect()->route('dashboard')->with('success', "Successfully subscribed to {$plan->name}!{$rolloverMessage}");
    }

    public function wallet()
    {
        return view('subscriptions.wallet');
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:50000',
        ]);

        $user = Auth::user();
        $user->increment('wallet_balance', $request->amount);

        // TODO: Integrate with payment gateway
        // For now, just add the amount

        return back()->with('success', 'Wallet topped up successfully!');
    }
}