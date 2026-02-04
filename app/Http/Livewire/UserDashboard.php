<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use App\Models\RadAcct;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * @method void emit(string $event, mixed ...$params)
 * @method void emitTo(string $component, string $event, mixed ...$params)
 * @method void emitSelf(string $event, mixed ...$params)
 * @method void dispatchBrowserEvent(string $event, array $payload = [])
 */
class UserDashboard extends Component
{
    use WithPagination;

    protected $listeners = [
        'subscribeEvent' => 'subscribe',
    ];

    protected $layout = 'layouts.app';

    public $voucherCode = '';

    public function subscribe(int $planId): void
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            Notification::make()
                ->title('Plan not found')
                ->body('The plan you selected could not be found.')
                ->danger()
                ->send();

            return;
        }

        $user = Auth::user();

        // If the user's current plan still has time remaining but no data, expire it immediately
        if ($user->plan_id && $user->plan_expiry && $user->plan_expiry->isFuture()) {
            $remaining = $user->remaining_data ?? 0;
            if ($remaining <= 0) {
                // expire current plan so new plan can activate
                $user->plan_id = null;
                $user->plan_expiry = null;
                $user->save(); // triggers observer -> PlanSyncService
            } else {
                Notification::make()
                    ->title('Subscription Active')
                    ->body('You already have an active plan. Please wait for it to expire.')
                    ->warning()
                    ->send();

                return;
            }
        }

        // Simulate free purchase / immediate activation
        // Convert plan limit to bytes (respecting unit)
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } else {
            $pval = (int) $plan->data_limit;
            if ($pval > 1048576) {
                $planBytes = $pval;
            } else {
                $planBytes = $plan->limit_unit === 'GB' ? (int) ($pval * 1073741824) : (int) ($pval * 1048576);
            }
        }

        $user->plan_id = $plan->id;
        $user->data_used = 0;
        $user->data_limit = is_null($planBytes) ? null : $planBytes;
        $user->plan_expiry = now()->addDays($plan->validity_days ?? 0);
        $user->plan_started_at = now();
        $user->save(); // triggers observer -> RADIUS sync

        Notification::make()
            ->title('Plan Activated!')
            ->body("You have successfully subscribed to {$plan->name}.")
            ->success()
            ->send();

        // Flash a session message as a fallback for environments where dispatchBrowserEvent() is unavailable
        session()->flash('toast_message', "You have successfully subscribed to {$plan->name}.");

        // Dispatch a browser event if available (safe check to avoid method missing errors)
        if (method_exists($this, 'dispatchBrowserEvent')) {
            $this->dispatchBrowserEvent('plan-activated', [
                'planName' => $plan->name,
                'planId' => $plan->id,
                'message' => "You have successfully subscribed to {$plan->name}."
            ]);
        }
    }

    public function render()
    {
        $user = Auth::user();

        $plans = Plan::all();

        // Active RADIUS session (acctstoptime NULL - no time restriction)
        $radiusReachable = true;
        try {
            $activeSession = RadAcct::forUser($user->username)
                ->active()
                ->latest('acctstarttime')
                ->first();
        } catch (\Exception $e) {
            // RADIUS server unreachable - log the error and set session to null
            Log::warning('RADIUS database connection failed in UserDashboard: ' . $e->getMessage());
            $activeSession = null;
            $radiusReachable = false;
        }

        // Live usage from the active session (kept for reference)
        $liveUsage = $activeSession ? (($activeSession->acctinputoctets ?? 0) + ($activeSession->acctoutputoctets ?? 0)) : 0;

        // Determine start date for counting usage (plan start). If missing, fallback to 1 year back to avoid huge scans
        $startDate = $user->plan_started_at ?? now()->subYears(1);

        // Identify the family master ID (parent if exists, else self)
        $masterId = $user->parent_id ?? $user->id;

        // Get the master user with plan loaded
        $masterUser = User::with('plan')->find($masterId);

        // Get all family usernames (master + children)
        $familyUsernames = User::where('id', $masterId)->orWhere('parent_id', $masterId)->pluck('username');

        // Sum all historical sessions for the family since the plan started
        $historyUsage = RadAcct::whereIn('username', $familyUsernames)
            ->where('acctstarttime', '>=', $startDate)
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

        // Total usage derived from radacct history
        $totalUsed = (int) $historyUsage;
        $formattedTotalUsed = Number::fileSize($totalUsed);

        // Current speed: show plan speed limits when online
        if ($activeSession && $user->plan) {
            $upload = $user->plan->speed_limit_upload ?? 0;
            $download = $user->plan->speed_limit_download ?? 0;
            $currentSpeed = ($download || $upload) ? ($download . 'k/' . $upload . 'k') : '0 kbps';
        } else {
            $currentSpeed = '0 kbps';
        }

        $subscriptionStatus = $masterUser->plan_id ? 'active' : 'inactive';
        $connectionStatus = ($activeSession) ? 'active' : 'offline';

        // If RADIUS is unreachable, show 'unknown' status
        if (!$radiusReachable) {
            $connectionStatus = 'unknown';
        }

        // Determine remaining bytes and check for exhaustion
        $remainingBytes = null;
        $formattedRemaining = null;

        if ($masterUser && $masterUser->plan && $masterUser->plan->limit_unit !== 'Unlimited' && $masterUser->plan->data_limit) {
            $planVal = (int) $masterUser->plan->data_limit;
            if ($planVal > 1048576) {
                $planBytes = $planVal;
            } else {
                $planBytes = $masterUser->plan->limit_unit === 'GB' ? (int) ($planVal * 1073741824) : (int) ($planVal * 1048576);
            }

            $remainingBytes = max(0, $planBytes - $totalUsed);
            $formattedRemaining = Number::fileSize($remainingBytes);

            // If used >= limit, mark subscription as exhausted
            if ($planBytes > 0 && $totalUsed >= $planBytes) {
                $subscriptionStatus = 'exhausted';
            }
        } else {
            $formattedRemaining = 'Unlimited';
        }

        // Prefer the framed IP from the active RADIUS session; fall back to user current_ip or 'Offline'
        if ($activeSession && !empty($activeSession->framedipaddress)) {
            $currentIp = $activeSession->framedipaddress;
        } elseif ($connectionStatus === 'active') {
            $currentIp = $user->current_ip ?? '-';
        } elseif ($connectionStatus === 'unknown') {
            $currentIp = 'Server Unreachable';
        } else {
            $currentIp = 'Offline';
        }

        // Formatted validity string (or 'N/A')
        $validUntil = $masterUser->plan_expiry ? $masterUser->plan_expiry->format('d M Y, h:i A') : 'N/A';
        $planValidityHuman = $masterUser->plan_validity_human;

        // Days remaining (signed diff, clamp to 0)
        if ($masterUser->plan_expiry) {
            $diff = now()->diffInDays($masterUser->plan_expiry, false);
            $subscriptionDays = (int) max(0, $diff);

            if ($subscriptionDays <= 7) {
                $daysBadgeClass = 'bg-red-500 text-white';
            } elseif ($subscriptionDays <= 30) {
                $daysBadgeClass = 'bg-yellow-500 text-black';
            } else {
                $daysBadgeClass = 'bg-green-500 text-white';
            }
        } else {
            $subscriptionDays = 0;
            $daysBadgeClass = 'bg-gray-500 text-white';
        }

        // Calculate data usage percentage based on totalUsed and master's plan data_limit (convert plan to bytes first)
        if ($masterUser && $masterUser->plan && $masterUser->plan->limit_unit !== 'Unlimited' && $masterUser->plan->data_limit) {
            $planVal = (int) $masterUser->plan->data_limit;
            if ($planVal > 1048576) {
                // already bytes
                $planBytes = $planVal;
            } else {
                $planBytes = $masterUser->plan->limit_unit === 'GB' ? (int) ($planVal * 1073741824) : (int) ($planVal * 1048576);
            }

            if ($planBytes > 0) {
                $percentage = ($totalUsed / (float) $planBytes) * 100;
                $dataUsagePercentage = (int) min(100, round($percentage));
            } else {
                $dataUsagePercentage = 0;
            }
        } else {
            $dataUsagePercentage = 0;
        }

        $uptime = $activeSession && $activeSession->acctstarttime ? Carbon::parse($activeSession->acctstarttime)->diffForHumans() : ($user->last_online ? $user->last_online->diffForHumans() : '-');

        // Fetch all transactions
        $recentTransactions = \App\Models\Transaction::where('user_id', Auth::id())
            ->with('plan')
            ->latest()
            ->paginate(10);

        return view('livewire.user-dashboard', [
            'user' => $user,
            'plans' => $plans,
            'totalUsed' => $totalUsed,
            'formattedTotalUsed' => $formattedTotalUsed,
            'formattedDataLimit' => $masterUser?->plan?->data_limit_human ?? 'Unlimited',
            'subscriptionStatus' => $subscriptionStatus,
            'connectionStatus' => $connectionStatus,
            'currentIp' => $currentIp,
            'validUntil' => $validUntil,
            'planValidityHuman' => $planValidityHuman,
            'subscriptionDays' => $subscriptionDays,
            'dataUsagePercentage' => $dataUsagePercentage,
            'currentSpeed' => $currentSpeed,
            'uptime' => $uptime,
            'daysBadgeClass' => $daysBadgeClass,
            'recentTransactions' => $recentTransactions,
            'radiusReachable' => $radiusReachable,
        ]);
    }

    public function forceActivate($subscriptionId)
    {
        $user = Auth::user();

        $subscription = $user->pendingSubscriptions()->find($subscriptionId);
        if (!$subscription) return;

        // Calculate Rollover (in bytes) using User accessor
        $rolloverBytes = $user->getRemainingDataAttribute();
        $plan = $subscription->plan;

        // Convert plan limit to bytes (respecting unit)
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } else {
            $pval = (int) $plan->data_limit;
            if ($pval > 1048576) {
                $planBytes = $pval;
            } else {
                $planBytes = $plan->limit_unit === 'GB' ? (int) ($pval * 1073741824) : (int) ($pval * 1048576);
            }
        }

        $newLimit = is_null($planBytes) ? null : ($planBytes + ($rolloverBytes ?? 0));

        // Activate
        $user->update([
            'plan_id' => $plan->id,
            'data_limit' => $newLimit,
            'data_used' => 0,
            'plan_expiry' => now()->addDays($plan->validity_days),
        ]);

        // Delete the processed subscription
        $subscription->delete();

        // Force Radius Sync
        $user->save();

        session()->flash('success', 'Plan activated! ' . Number::fileSize($rolloverBytes) . ' rolled over.');
    }

    public function redeemVoucher()
    {
        // 1. Validate
        $this->validate([
            'voucherCode' => 'required|string|exists:vouchers,code',
        ]);
    // 2. Find Voucher
    $voucher = \App\Models\Voucher::where('code', $this->voucherCode)->first();
    // 3. Check if Used
    if ($voucher->is_used) {
        $this->addError('voucherCode', 'This voucher has already been used.');
        return;
    }
    $user = \Illuminate\Support\Facades\Auth::user();
    $newPlan = $voucher->plan;
    // 4. THE DECISION: Queue or Activate?
    // SCENARIO A: User has an ACTIVE plan -> Queue It (unless data exhausted)
    if ($user->plan_expiry && $user->plan_expiry > now()) {
        // If current plan has no remaining data -> expire it and activate immediately
        if (($user->remaining_data ?? 0) <= 0) {
            $user->plan_id = null;
            $user->plan_expiry = null;
            $user->save(); // triggers PlanSyncService -> will set radusergroup to default_group
            // fall through to activation
        } else {
            // Add to Queue
            \App\Models\PendingSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
            ]);
            session()->flash('success', "Voucher accepted! {$newPlan->name} added to your queue.");
            return;
        }
    }
    // SCENARIO B: User is EXPIRED or NEW -> Activate Immediately
    else {
        // Calculate rollover in bytes using accessor
        $rolloverBytes = $user->getRemainingDataAttribute();

        // Convert new plan limit to bytes (respect unit)
        if ($newPlan->limit_unit === 'Unlimited') {
            $newPlanBytes = null;
        } else {
            $npval = (int) $newPlan->data_limit;
            if ($npval > 1048576) {
                $newPlanBytes = $npval;
            } else {
                $newPlanBytes = $newPlan->limit_unit === 'GB' ? (int) ($npval * 1073741824) : (int) ($npval * 1048576);
            }
        }

        $newLimit = is_null($newPlanBytes) ? null : ($newPlanBytes + ($rolloverBytes ?? 0));

        $user->update([
            'plan_id' => $newPlan->id,
            'data_limit' => $newLimit,
            'data_used' => 0,
            'plan_expiry' => now()->addDays($newPlan->validity_days),
        ]);

        // Note: The UserObserver will automatically sync this to Radius/MikroTik
        $msg = "Success! {$newPlan->name} is now active.";
        if (($rolloverBytes ?? 0) > 0) {
            $msg .= ' ' . Number::fileSize($rolloverBytes) . ' rolled over.';
        }
        session()->flash('success', $msg);
    }

    // 5. Mark Voucher as Used
    $voucher->update([
        'is_used' => true,
        'used_by' => $user->id,
        'used_at' => now(),
    ]);

    // 6. Create Transaction Record
    try {
        \Illuminate\Support\Facades\Log::info("Attempting to create transaction for voucher {$voucher->code}", [
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->price,
            'reference' => 'VCH-' . $voucher->code,
            'status' => 'success',
            'gateway' => 'voucher',
            'paid_at' => now(),
        ]);

        $transaction = \App\Models\Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->price,
            'reference' => 'VCH-' . $voucher->code,
            'status' => 'success',
            'gateway' => 'voucher',
            'paid_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info("Transaction created successfully for voucher {$voucher->code} with ID: {$transaction->id}");
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Failed to create transaction for voucher {$voucher->code}: " . $e->getMessage(), [
            'exception' => $e,
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'plan_exists' => \App\Models\Plan::find($newPlan->id) ? 'yes' : 'no',
            'user_exists' => \App\Models\User::find($user->id) ? 'yes' : 'no',
        ]);
        // Continue execution even if transaction creation fails
    }

    // 7. Reset Form
    $this->reset('voucherCode');
    }
}
