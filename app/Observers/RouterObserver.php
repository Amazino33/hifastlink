<?php

namespace App\Observers;

use App\Models\Router;
use App\Models\Nas;
use Illuminate\Support\Facades\Log;

class RouterObserver
{
    /**
     * Handle the Router "created" event.
     */
    public function created(Router $router): void
    {
        $this->syncToRadiusNas($router);
    }

    /**
     * Handle the Router "updated" event.
     */
    public function updated(Router $router): void
    {
        $this->syncToRadiusNas($router);
    }

    /**
     * Handle the Router "deleted" event.
     */
    public function deleted(Router $router): void
    {
        // Remove from RADIUS NAS table
        Nas::where('nasname', $router->ip_address)->delete();
        
        Log::info("Removed router from RADIUS NAS table", [
            'router' => $router->name,
            'ip' => $router->ip_address,
        ]);
    }

    /**
     * Sync router to RADIUS nas table
     */
    protected function syncToRadiusNas(Router $router): void
    {
        Nas::updateOrCreate(
            ['nasname' => $router->ip_address],
            [
                'shortname' => $router->nas_identifier,
                'type' => 'other', // or 'mikrotik'
                'ports' => 1812,
                'secret' => $router->secret,
                'server' => null,
                'community' => null,
                'description' => $router->name . ' - ' . $router->location,
            ]
        );

        Log::info("Synced router to RADIUS NAS table", [
            'router' => $router->name,
            'ip' => $router->ip_address,
            'nas_identifier' => $router->nas_identifier,
        ]);
    }
}
