<?php

namespace App\Http\Controllers;

use App\Models\DataPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Update user subscription
        $user->update([
            'data_limit' => $plan->data_limit,
            'subscription_end_date' => now()->addDays($plan->duration_days),
            'connection_status' => 'active',
        ]);

        // Reset data usage
        $user->update(['data_used' => 0]);

        // Sync to RADIUS with new limits
        \Artisan::call('radius:sync-users');

        return redirect()->route('dashboard')->with('success', "Successfully subscribed to {$plan->name}!");
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