<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule RADIUS data usage sync every minute
Schedule::command('radius:sync-data-usage')->everyMinute();

// Sync connected devices from RadAcct into devices table every minute
Schedule::command('radius:sync-device')->everyMinute();

// Sync users to RADIUS every minute (adjust as needed)
Schedule::command('radius:sync-users')->everyMinute();

// Sync RADIUS data usage every minute (adjust as needed)
Schedule::command('sync:radius')->everyMinute();

// Check data limits every hour
Schedule::command('network:check-limits')->hourly();

// Kick expired users every minute
Schedule::command('users:kick-expired')->everyMinute();

// Check subscriptions expiry daily and snapshot rollover bytes
Schedule::command('subscriptions:check-expiry')->dailyAt('02:00');

// Generate daily reports at 1 AM
Schedule::command('reports:generate --type=daily')->dailyAt('01:00');

// Generate monthly reports on the 1st of each month
Schedule::command('reports:generate --type=monthly')->monthlyOn(1, '02:00');



Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
