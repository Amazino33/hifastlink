<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync RADIUS data usage every minute (adjust as needed)
        $schedule->command('sync:radius')->everyMinute();

        // Sync RADIUS data usage every 5 minutes (legacy/other commands kept for compatibility)
        $schedule->command('radius:sync-data-usage')->everyFiveMinutes();

        // Sync users to RADIUS every 5 minutes
        $schedule->command('radius:sync-users')->everyFiveMinutes();

        // Sync connected devices from RadAcct into devices table
        $schedule->command('radius:sync-devices')->everyMinute();

        // Check data limits every hour
        $schedule->command('network:check-limits')->hourly();

        // Kick expired users every minute
        $schedule->command('users:kick-expired')->everyMinute();

        // Check subscriptions expiry daily and snapshot rollover bytes
        $schedule->command('subscriptions:check-expiry')->dailyAt('02:00');

        // Generate daily reports at 1 AM
        $schedule->command('reports:generate --type=daily')->dailyAt('01:00');

        // Generate monthly reports on the 1st of each month
        $schedule->command('reports:generate --type=monthly')->monthlyOn(1, '02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}