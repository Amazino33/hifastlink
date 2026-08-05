<?php

namespace App\Console\Commands;

use App\Models\RouterPayout;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyPayouts extends Command
{
    protected $signature = 'payouts:generate {--year= : Year (defaults to last month)} {--month= : Month (defaults to last month)}';

    protected $description = 'Auto-generate pending payout records for all router owners for the previous month';

    public function handle(): void
    {
        $year  = (int) ($this->option('year')  ?: now()->subMonth()->year);
        $month = (int) ($this->option('month') ?: now()->subMonth()->month);

        $this->info("Generating payouts for {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '...');

        $count = RouterPayout::generateForMonth($year, $month);

        if ($count === 0) {
            $this->warn('No new payouts generated — all routers had ₦0 revenue or already have records for this period.');
        } else {
            $this->info("{$count} payout(s) created and waiting for admin approval.");
        }

        Log::info("payouts:generate completed — {$count} payout(s) created for {$year}-{$month}");
    }
}
