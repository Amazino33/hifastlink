<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\RadAcct;

class ReportUnmatchedRouterRefs extends Command
{
    protected $signature = 'router:report-unmatched {--sample=5} {--days=7}';
    protected $description = 'Report transactions and payments that still have null router_id and show samples with nearby RADIUS sessions';

    public function handle(): int
    {
        $sample = (int) $this->option('sample');
        $days = (int) $this->option('days');

        $txNull = Transaction::whereNull('router_id')->count();
        $payNull = Payment::whereNull('router_id')->count();

        $this->info("Transactions with null router_id: {$txNull}");
        $this->info("Payments with null router_id: {$payNull}");

        if ($txNull > 0) {
            $this->info("Sample transactions (showing nearby RADIUS sessions within ±{$days} days):");
            $rows = Transaction::whereNull('router_id')->orderByDesc('created_at')->limit($sample)->get();
            foreach ($rows as $r) {
                $username = optional($r->user)->username;
                $this->line("tx id={$r->id} user_id={$r->user_id} username={$username} amount={$r->amount} ref={$r->reference} created_at={$r->created_at}");
                $this->showNearbySessions($r->created_at, $username, $days);
            }
        }

        if ($payNull > 0) {
            $this->info("Sample payments (showing nearby RADIUS sessions within ±{$days} days):");
            $rows = Payment::whereNull('router_id')->orderByDesc('created_at')->limit($sample)->get();
            foreach ($rows as $r) {
                $username = optional($r->user)->username;
                $this->line("pay id={$r->id} user_id={$r->user_id} username={$username} amount={$r->amount} ref={$r->reference} created_at={$r->created_at}");
                $this->showNearbySessions($r->created_at, $username, $days);
            }
        }

        return 0;
    }

    protected function showNearbySessions($timestamp, $username, $days)
    {
        if (! $username) {
            $this->line("  No username available for this record.");
            return;
        }

        $start = $timestamp->copy()->subDays($days);
        $end = $timestamp->copy()->addDays($days);

        $sessions = RadAcct::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->whereBetween('acctstarttime', [$start, $end])
            ->orderByDesc('acctstarttime')
            ->limit(5)
            ->get();

        if ($sessions->isEmpty()) {
            $this->line("  No RADIUS sessions found within ±{$days} days for username={$username}");
            return;
        }

        foreach ($sessions as $s) {
            $this->line("  session id={$s->id} nasip={$s->nasipaddress} nasid={$s->nasidentifier} start={$s->acctstarttime}");
        }
    }
}
