<?php

namespace App\Http\Controllers;

use App\Models\DataPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = DataPlan::active()->orderBy('sort_order')->get();
        return view('subscriptions.plans', compact('plans'));
    }

    public function subscribe(Request $request, DataPlan $plan)
    {
        $user = Auth::user();

        // Check if user can afford the plan
        if ($user->wallet_balance < $plan->price) {
            return back()->with('error', 'Insufficient balance. Please top up your wallet.');
        }

        // Deduct from wallet
        $user->decrement('wallet_balance', $plan->price);

        // Rollover data (stack existing balance with new plan amount)
        $currentBalance = $user->data_balance ?? 0;
        $newDataBalance = $currentBalance + ($plan->data_limit ?? 0);

        // Rollover time (extend if active, start from now if expired)
        $now = Carbon::now();
        if ($user->plan_expiry && $user->plan_expiry->gt($now)) {
            $newExpiry = Carbon::parse($user->plan_expiry)->addDays($plan->duration_days);
        } else {
            $newExpiry = $now->copy()->addDays($plan->duration_days);
        }

        // Update user subscription with stacked values
        $user->update([
            'data_balance' => $newDataBalance,
            'plan_expiry' => $newExpiry,
            'connection_status' => 'active',
        ]);

        // Sync to RADIUS with new limits
        \Artisan::call('radius:sync-users');

        return redirect()->route('dashboard')->with('success', "Successfully subscribed to {$plan->name}! Data and time have been stacked.");
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