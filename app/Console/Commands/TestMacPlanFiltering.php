<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlanFilterService;
use App\Models\MacPlanAssignment;
use App\Models\DataPlan;
use App\Models\Router;

class TestMacPlanFiltering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:nas-plan-filtering {nas?} {router_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NAS identifier based plan filtering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nasIdentifier = $this->argument('nas') ?? 'router-001';
        $routerId = $this->argument('router_id') ?? 1;

        $this->info("Testing NAS plan filtering for NAS: {$nasIdentifier}, Router ID: {$routerId}");

        $planFilterService = new PlanFilterService();

        // Check if NAS has custom assignments
        $hasCustom = $planFilterService->hasCustomAssignments($nasIdentifier, $routerId);
        $this->info("Has custom assignments: " . ($hasCustom ? 'Yes' : 'No'));

        // Get available plans
        $plans = $planFilterService->getAvailablePlans($nasIdentifier, $routerId);
        $this->info("Available plans: " . $plans->count());

        foreach ($plans as $plan) {
            $this->line("  - {$plan->name} (Custom: " . ($plan->is_custom ? 'Yes' : 'No') . ")");
        }

        // Show current NAS assignments
        $assignments = MacPlanAssignment::findByNasAndRouter($nasIdentifier, $routerId);
        $this->info("Current assignments for this NAS/router: " . $assignments->count());

        foreach ($assignments as $assignment) {
            $this->line("  - Plan: {$assignment->dataPlan->name}, Active: " . ($assignment->is_active ? 'Yes' : 'No'));
        }

        return Command::SUCCESS;
    }
}