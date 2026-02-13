<?php

namespace App\Services;

use App\Models\DataPlan;
use App\Models\Router;
use App\Models\User;

class DataPlanService
{
    /**
     * Get visible data plans for a user based on their router connection.
     */
    public function getVisiblePlansForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $userRouter = $this->getUserRouter($user);

        if (!$userRouter) {
            // User not connected to any router, show universal plans only
            return DataPlan::active()->universal()->orderBy('sort_order')->get();
        }

        // Check if the router has any custom plans
        $hasCustomPlans = DataPlan::where('router_id', $userRouter->id)
            ->where('is_custom', true)
            ->where('is_active', true)
            ->exists();

        if ($hasCustomPlans) {
            // Router has custom plans, check if universal plans should be shown
            $showUniversal = DataPlan::where('router_id', $userRouter->id)
                ->where('is_custom', true)
                ->where('show_universal_plans', true)
                ->exists();

            $query = DataPlan::active()->where('router_id', $userRouter->id);

            if ($showUniversal) {
                $query->orWhere('is_custom', false);
            }

            return $query->orderBy('sort_order')->get();
        }

        // No custom plans for this router, show universal plans
        return DataPlan::active()->universal()->orderBy('sort_order')->get();
    }

    /**
     * Get the router a user is currently connected to.
     */
    public function getUserRouter(User $user): ?Router
    {
        // This is a simplified implementation
        // In a real scenario, you might check active RADIUS sessions
        // or use some other method to determine the user's current router

        // For now, we'll assume users are associated with routers through some means
        // You may need to implement this based on your RADIUS setup

        // Example: Check if user has an active session with a specific NAS
        $activeSession = $user->radAcct()
            ->whereNull('acctstoptime')
            ->latest('acctstarttime')
            ->first();

        if ($activeSession && $activeSession->nasipaddress) {
            return Router::where('ip_address', $activeSession->nasipaddress)->first();
        }

        return null;
    }

    /**
     * Check if a router has custom plans.
     */
    public function routerHasCustomPlans(Router $router): bool
    {
        return DataPlan::where('router_id', $router->id)
            ->where('is_custom', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get custom plans for a specific router.
     */
    public function getCustomPlansForRouter(Router $router): \Illuminate\Database\Eloquent\Collection
    {
        return DataPlan::where('router_id', $router->id)
            ->where('is_custom', true)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }
}