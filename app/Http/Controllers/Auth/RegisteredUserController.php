<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\Router;
use App\Models\User;
use App\Services\PlanSyncService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class, 'alpha_num'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'username'          => $request->username,
            'email'             => $request->email,
            'phone'             => $request->phone,
            'password'          => Hash::make($request->password),
            'radius_password'   => $request->password,
            'data_limit'        => 1000000000,
            'connection_status' => 'active',
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Free trial bonus — triggered by ?bonus=free_trial&router={nas_identifier} on the QR code
        if ($request->input('bonus') === 'free_trial') {
            $routerIdentifier = $request->input('router');
            $this->applyFreeTrial($user, $routerIdentifier);

            if ($routerIdentifier) {
                return redirect()->route('dashboard', ['router' => $routerIdentifier]);
            }
        }

        return redirect(route('dashboard', absolute: false));
    }

    private function applyFreeTrial(User $user, ?string $routerIdentifier): void
    {
        if (! AppSetting::bool('free_wifi_enabled', false)) {
            return;
        }

        $planId    = AppSetting::get('free_wifi_plan_id');
        $trialPlan = $planId ? Plan::where('id', $planId)->where('is_active', true)->first() : null;

        if (! $trialPlan || $user->free_trial_claimed_at !== null) {
            return;
        }

        if ($routerIdentifier) {
            $router = Router::where('nas_identifier', $routerIdentifier)->where('is_active', true)->first();
            if ($router && ! $user->router_id) {
                $user->router_id = $router->id;
            }
        }

        $user->plan_id               = $trialPlan->id;
        $user->data_used             = 0;
        $user->data_limit            = null;
        $user->plan_expiry           = now()->addDays($trialPlan->validity_days ?: 1);
        $user->plan_started_at       = now();
        $user->free_trial_claimed_at = now();
        $user->save();

        PlanSyncService::syncUserPlan($user->fresh());
    }
}
