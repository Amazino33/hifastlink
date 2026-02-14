<?php

// Debug script to check router and plan filtering
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Router Check ===\n";
$routers = \App\Models\Router::all();
echo "Total routers: " . $routers->count() . "\n";
foreach ($routers as $router) {
    echo "- ID: {$router->id}, Name: {$router->name}, NAS: {$router->nas_identifier}\n";
}

echo "\n=== User Router Check ===\n";
$users = \App\Models\User::whereNotNull('router_id')->get();
echo "Users with router_id: " . $users->count() . "\n";
foreach ($users as $user) {
    echo "- User: {$user->name} ({$user->id}), Router ID: {$user->router_id}\n";
}

echo "\n=== Plan Check ===\n";
$plans = \App\Models\Plan::all();
echo "Total plans: " . $plans->count() . "\n";

$customPlans = \App\Models\Plan::where('is_custom', true)->get();
echo "Custom plans: " . $customPlans->count() . "\n";
foreach ($customPlans as $plan) {
    echo "- Plan: {$plan->name} (ID: {$plan->id}), Router ID: " . ($plan->router_id ?? 'NULL') . "\n";
}

$routerPlans = \App\Models\Plan::whereNotNull('router_id')->get();
echo "Plans with router_id: " . $routerPlans->count() . "\n";
foreach ($routerPlans as $plan) {
    echo "- Plan: {$plan->name} (ID: {$plan->id}), Router ID: {$plan->router_id}\n";
}

echo "\n=== MacPlanAssignment Check ===\n";
$assignments = \App\Models\MacPlanAssignment::all();
echo "Total assignments: " . $assignments->count() . "\n";
foreach ($assignments as $assignment) {
    echo "- NAS: {$assignment->nas_identifier}, Router ID: {$assignment->router_id}, Plan ID: {$assignment->plan_id}\n";
}