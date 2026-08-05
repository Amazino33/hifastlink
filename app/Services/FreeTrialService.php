<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\Router;
use App\Models\User;

class FreeTrialService
{
    public static function apply(User $user, ?string $routerIdentifier): void
    {
        if (! AppSetting::bool('free_wifi_enabled', false)) {
            return;
        }

        $planId    = AppSetting::get('free_wifi_plan_id');
        $trialPlan = $planId ? Plan::where('id', $planId)->where('is_active', true)->first() : null;

        if (! $trialPlan || $user->free_trial_claimed_at !== null) {
            return;
        }

        if ($routerIdentifier && ! $user->router_id) {
            $router = Router::where('nas_identifier', $routerIdentifier)->where('is_active', true)->first();
            if ($router) {
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
