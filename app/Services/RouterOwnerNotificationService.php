<?php

namespace App\Services;

use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class RouterOwnerNotificationService
{
    private WhatsAppService $wa;

    public function __construct()
    {
        $this->wa = new WhatsAppService();
    }

    public function notifyNewSubscription(User $subscriber, Router $router): void
    {
        $owner = $router->owner;
        if (! $owner || ! $owner->phone) return;

        $msg = "*New Subscriber* on {$router->name}\n\n"
            . "Name: {$subscriber->display_name}\n"
            . "Phone: " . ($subscriber->phone ?? '—') . "\n"
            . "Plan: " . ($subscriber->plan?->name ?? 'Custom') . "\n"
            . "Expires: " . ($subscriber->plan_expiry?->format('d M Y') ?? '—');

        $this->send($owner->phone, $msg, 'new_subscription', $router);
    }

    public function notifyRouterOffline(Router $router): void
    {
        $owner = $router->owner;
        if (! $owner || ! $owner->phone) return;

        $msg = "*Router Offline* \u{26A0}\n\n"
            . "Router: {$router->name}\n"
            . "Location: {$router->location}\n"
            . "Last seen: " . ($router->last_seen_at?->diffForHumans() ?? 'Unknown') . "\n\n"
            . "Please check the router's power and internet connection.";

        $this->send($owner->phone, $msg, 'router_offline', $router);
    }

    public function notifyRouterOnline(Router $router): void
    {
        $owner = $router->owner;
        if (! $owner || ! $owner->phone) return;

        $msg = "*Router Back Online* \u{2705}\n\n"
            . "Router: {$router->name}\n"
            . "Location: {$router->location}\n"
            . "Status: Online and accepting connections.";

        $this->send($owner->phone, $msg, 'router_online', $router);
    }

    public function notifyNewPlanSubscription(User $subscriber, Router $router, $plan): void
    {
        $owner = $router->owner;
        if (! $owner || ! $owner->phone) return;

        $msg = "*New Plan Purchase* on {$router->name}\n\n"
            . "User: {$subscriber->display_name}\n"
            . "Phone: " . ($subscriber->phone ?? '—') . "\n"
            . "Plan: {$plan->name}\n"
            . "Amount: \u{20A6}" . number_format($plan->price) . "\n"
            . "Duration: {$plan->validity_days} days";

        $this->send($owner->phone, $msg, 'new_plan', $router);
    }

    public function sendDailySummary(Router $router): void
    {
        $owner = $router->owner;
        if (! $owner || ! $owner->phone) return;

        $activeUsers = $router->activeSessions()->distinct('username')->count('username');
        $todayBytes = $router->sessions()
            ->whereDate('acctstarttime', today())
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
        $totalSubs = User::where('router_id', $router->id)->whereNotNull('plan_id')->count();

        $msg = "*Daily Summary* for {$router->name}\n"
            . date('d M Y') . "\n\n"
            . "Online now: {$activeUsers}\n"
            . "Total subscribers: {$totalSubs}\n"
            . "Data used today: " . Number::fileSize($todayBytes) . "\n"
            . "Router status: " . ($router->is_online ? 'Online' : 'Offline');

        $this->send($owner->phone, $msg, 'daily_summary', $router);
    }

    private function send(string $phone, string $message, string $type, Router $router): void
    {
        try {
            $this->wa->send($phone, $message);
        } catch (\Throwable $e) {
            Log::error("RouterOwnerNotification [{$type}] failed", [
                'router' => $router->nas_identifier,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
