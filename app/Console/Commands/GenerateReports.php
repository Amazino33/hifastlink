<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateReports extends Command
{
    protected $signature = 'reports:generate {--type=daily}';
    protected $description = 'Generate usage and revenue reports';

    public function handle()
    {
        $type = $this->option('type');

        switch ($type) {
            case 'daily':
                $this->generateDailyReport();
                break;
            case 'monthly':
                $this->generateMonthlyReport();
                break;
            default:
                $this->error('Invalid report type. Use --type=daily or --type=monthly');
        }
    }

    private function generateDailyReport()
    {
        $date = now()->subDay()->toDateString();

        $stats = [
            'date' => $date,
            'new_users' => User::whereDate('created_at', $date)->count(),
            'active_sessions' => \DB::table('user_sessions')
                                   ->whereDate('created_at', $date)
                                   ->count(),
            'total_data_used' => \DB::table('user_sessions')
                                   ->whereDate('created_at', $date)
                                   ->sum('used_bytes'),
            'revenue' => 0, // You'll need to track actual payments
        ];

        // Save to database or send email
        \Log::info('Daily Report Generated', $stats);

        $this->info('Daily report generated for ' . $date);
        $this->table(
            ['Metric', 'Value'],
            [
                ['New Users', $stats['new_users']],
                ['Active Sessions', $stats['active_sessions']],
                ['Data Used (GB)', number_format($stats['total_data_used'] / 1024 / 1024 / 1024, 2)],
                ['Revenue', '₦' . number_format($stats['revenue'], 2)],
            ]
        );
    }

    private function generateMonthlyReport()
    {
        $month = now()->subMonth()->format('Y-m');

        // Similar logic for monthly reports
        $this->info('Monthly report generated for ' . $month);
    }
}