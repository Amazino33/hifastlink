<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\MacPlanAssignment;
use App\Models\Router;
use Illuminate\Database\Eloquent\Collection;

class PlanFilterService
{
    /**
     * Get available plans for a user based on NAS identifier and router.
     *
     * @param string|null $routerIdentifier
     * @param int|null $routerId
     * @return Collection
     */
    public function getAvailablePlans(?string $routerIdentifier = null, ?int $routerId = null): Collection
    {
        // If no NAS identifier or router ID provided, return all active plans
        if (!$routerIdentifier || !$routerId) {
            return Plan::where('is_active', true)->orderBy('sort_order')->get();
        }

        // Check if this NAS identifier has custom plan assignments for this router
        $nasAssignments = MacPlanAssignment::findByNasAndRouter($routerIdentifier, $routerId);

        if ($nasAssignments->isNotEmpty()) {
            // Get the custom plans assigned to this NAS identifier
            $customPlanIds = $nasAssignments->pluck('plan_id')->unique();

            $plans = Plan::whereIn('id', $customPlanIds)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            // If the custom plans have show_universal_plans enabled, also include universal plans
            $showUniversal = $nasAssignments->first()->plan->show_universal_plans ?? false;
            if ($showUniversal) {
                $universalPlans = Plan::where('is_active', true)
                    ->whereNull('router_id')
                    ->where('is_custom', false)
                    ->orderBy('sort_order')
                    ->get();

                $plans = $plans->merge($universalPlans);
            }

            return $plans->sortBy('sort_order');
        }

        // No custom assignments found, return all active plans
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Check if a router identifier has custom plan assignments for a router.
     *
     * @param string $routerIdentifier
     * @param int $routerId
     * @return bool
     */
    public function hasCustomAssignments(string $routerIdentifier, int $routerId): bool
    {
        return MacPlanAssignment::where('nas_identifier', $routerIdentifier)
            ->where('router_id', $routerId)
            ->active()
            ->exists();
    }

    /**
     * Get custom plans assigned to a router identifier for a router.
     *
     * @param string $routerIdentifier
     * @param int $routerId
     * @return Collection
     */
    public function getCustomPlansForNas(string $routerIdentifier, int $routerId): Collection
    {
        return MacPlanAssignment::findByNasAndRouter($routerIdentifier, $routerId)
            ->map(function ($assignment) {
                return $assignment->plan;
            })
            ->filter()
            ->unique('id');
    }
}