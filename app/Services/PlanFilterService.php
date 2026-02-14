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
        \Log::info('PlanFilterService: getAvailablePlans called', [
            'router_identifier' => $routerIdentifier,
            'router_id' => $routerId
        ]);

        // If no NAS identifier or router ID provided, return all active plans
        if (!$routerIdentifier || !$routerId) {
            \Log::info('PlanFilterService: No router info, returning all plans');
            return Plan::where('is_active', true)->orderBy('sort_order')->get();
        }

        // Check if this NAS identifier has custom plan assignments for this router
        $nasAssignments = MacPlanAssignment::findByNasAndRouter($routerIdentifier, $routerId);
        \Log::info('PlanFilterService: MacPlanAssignment check', [
            'router_identifier' => $routerIdentifier,
            'router_id' => $routerId,
            'assignments_count' => $nasAssignments->count()
        ]);

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

            \Log::info('PlanFilterService: Returning MacPlanAssignment plans', [
                'plans_count' => $plans->count(),
                'plan_names' => $plans->pluck('name')->toArray()
            ]);

            return $plans->sortBy('sort_order');
        }

        // No MacPlanAssignment records found, check for plans directly assigned to this router
        $routerPlans = Plan::where('router_id', $routerId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        \Log::info('PlanFilterService: Router plans check', [
            'router_id' => $routerId,
            'router_plans_count' => $routerPlans->count(),
            'router_plan_names' => $routerPlans->pluck('name')->toArray()
        ]);

        if ($routerPlans->isNotEmpty()) {
            // If router plans have show_universal_plans enabled, include universal plans
            $showUniversal = $routerPlans->first()->show_universal_plans ?? false;
            if ($showUniversal) {
                $universalPlans = Plan::where('is_active', true)
                    ->whereNull('router_id')
                    ->where('is_custom', false)
                    ->orderBy('sort_order')
                    ->get();

                $routerPlans = $routerPlans->merge($universalPlans);
            }

            \Log::info('PlanFilterService: Returning router plans', [
                'plans_count' => $routerPlans->count(),
                'plan_names' => $routerPlans->pluck('name')->toArray()
            ]);

            return $routerPlans->sortBy('sort_order');
        }

        // No custom assignments or router plans found, return all active plans
        \Log::info('PlanFilterService: No custom plans found, returning all plans');
        $allPlans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        \Log::info('PlanFilterService: All plans count', [
            'all_plans_count' => $allPlans->count()
        ]);
        return $allPlans;
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