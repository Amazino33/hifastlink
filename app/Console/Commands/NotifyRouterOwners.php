<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\RouterOwnerNotificationService;
use Illuminate\Console\Command;

class NotifyRouterOwners extends Command
{
    protected $signature = 'routers:notify-owners {--type=offline : Type of notification: offline, daily}';
    protected $description = 'Send WhatsApp notifications to router owners (offline alerts, daily summaries)';

    public function handle(): int
    {
        $type = $this->option('type');
        $service = new RouterOwnerNotificationService();

        $ownedRouters = Router::whereNotNull('owner_id')->with('owner')->get();

        if ($ownedRouters->isEmpty()) {
            $this->info('No owned routers found.');
            return 0;
        }

        $count = 0;

        foreach ($ownedRouters as $router) {
            if (! $router->owner?->phone) continue;

            if ($type === 'offline' && ! $router->is_online && ! $router->offline_notified_at) {
                $service->notifyRouterOffline($router);
                $router->offline_notified_at = now();
                $router->save();
                $this->line("Offline alert sent for {$router->name} → {$router->owner->display_name}");
                $count++;
            } elseif ($type === 'daily') {
                $service->sendDailySummary($router);
                $this->line("Daily summary sent for {$router->name} → {$router->owner->display_name}");
                $count++;
            }
        }

        $this->info("{$count} notification(s) sent.");
        return 0;
    }
}
