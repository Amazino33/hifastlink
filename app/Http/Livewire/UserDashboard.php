<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use App\Models\RadAcct;
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
    protected $listeners = [
        'subscribeEvent' => 'subscribe',
    ];

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

        if ($user->plan_id && $user->plan_expiry && $user->plan_expiry->isFuture()) {
            Notification::make()
                ->title('Subscription Active')
                ->body('You already have an active plan. Please wait for it to expire.')
                ->warning()
                ->send();

            return;
        }

        // Simulate free purchase / immediate activation
        $user->plan_id = $plan->id;
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

        // Active RADIUS session (acctstoptime NULL)
        $activeSession = RadAcct::forUser($user->username)->active()->latest('acctstarttime')->first();

        // Live usage from the active session (kept for reference)
        $liveUsage = $activeSession ? (($activeSession->acctinputoctets ?? 0) + ($activeSession->acctoutputoctets ?? 0)) : 0;

        // Determine start date for counting usage (plan start). If missing, fallback to 1 year back to avoid huge scans
        $startDate = $user->plan_started_at ?? now()->subYears(1);

        // Sum all historical sessions for this user since the plan started
        $historyUsage = RadAcct::where('username', $user->username)
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

        $subscriptionStatus = $user->plan_id ? 'active' : 'inactive';
        $connectionStatus = ($activeSession) ? 'active' : 'offline';

        // Prefer the framed IP from the active RADIUS session; fall back to user current_ip or 'Offline'
        if ($activeSession && !empty($activeSession->framedipaddress)) {
            $currentIp = $activeSession->framedipaddress;
        } elseif ($connectionStatus === 'active') {
            $currentIp = $user->current_ip ?? '-';
        } else {
            $currentIp = 'Offline';
        }

        // Formatted validity string (or 'N/A')
        $validUntil = $user->plan_expiry ? $user->plan_expiry->format('d M Y, h:i A') : 'N/A';
        $planValidityHuman = $user->plan_expiry ? Carbon::parse($user->plan_expiry)->diffForHumans() : '-';

        // Days remaining (signed diff, clamp to 0)
        if ($user->plan_expiry) {
            $diff = now()->diffInDays($user->plan_expiry, false);
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

        // Calculate data usage percentage based on totalUsed and plan data_limit
        if ($user->plan && ! empty($user->plan->data_limit) && $user->plan->data_limit > 0) {
            $percentage = ($totalUsed / (float) $user->plan->data_limit) * 100;
            $dataUsagePercentage = (int) min(100, round($percentage));
        } else {
            $dataUsagePercentage = 0;
        }

        $uptime = $activeSession && $activeSession->acctstarttime ? Carbon::parse($activeSession->acctstarttime)->diffForHumans() : ($user->last_online ? $user->last_online->diffForHumans() : '-');

        return view('livewire.user-dashboard', [
            'user' => $user,
            'plans' => $plans,
            'totalUsed' => $totalUsed,
            'formattedTotalUsed' => $formattedTotalUsed,
            'formattedDataLimit' => $user->plan?->data_limit ? Number::fileSize($user->plan->data_limit) : 'Unlimited',
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
        ])->layout('layouts.app');
    }
}
