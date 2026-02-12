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

        // Basic RADIUS inspection
        $radacctCount = \App\Models\RadAcct::count();
        $this->info("RADIUS records count (radacct): {$radacctCount}");
        $this->info("Sample RADIUS entries:");
        $sampleRad = \App\Models\RadAcct::orderByDesc('acctstarttime')->limit(5)->get();
        foreach ($sampleRad as $sr) {
            $this->line("  session id={$sr->id} username={$sr->username} nasip={$sr->nasipaddress} nasid={$sr->nasidentifier} start={$sr->acctstarttime}");
        }

        if ($txNull > 0) {
            $this->info("Sample transactions (showing nearby RADIUS sessions within ±{$days} days):");
            $rows = Transaction::whereNull('router_id')->orderByDesc('created_at')->limit($sample)->get();
            foreach ($rows as $r) {
                $username = optional($r->user)->username;
                $this->line("tx id={$r->id} user_id={$r->user_id} username={$username} amount={$r->amount} ref={$r->reference} created_at={$r->created_at}");
                $this->showNearbySessions($r->created_at, $username, $days);

                // If sessions found but no router matched, show candidate router identifiers present
                $sessions = \App\Models\RadAcct::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->whereBetween('acctstarttime', [$r->created_at->copy()->subDays($days), $r->created_at->copy()->addDays($days)])->get();
                if ($sessions->isNotEmpty()) {
                    $names = $sessions->pluck('nasidentifier')->filter()->unique()->implode(', ');
                    $ips = $sessions->pluck('nasipaddress')->filter()->unique()->implode(', ');
                    $this->line("  Candidate NAS identifiers: {$names}");
                    $this->line("  Candidate NAS IPs: {$ips}");
                }
            }
        }

        if ($payNull > 0) {
            $this->info("Sample payments (showing nearby RADIUS sessions within ±{$days} days):");
            $rows = Payment::whereNull('router_id')->orderByDesc('created_at')->limit($sample)->get();
            foreach ($rows as $r) {
                $username = optional($r->user)->username;
                $this->line("pay id={$r->id} user_id={$r->user_id} username={$username} amount={$r->amount} ref={$r->reference} created_at={$r->created_at}");
                $this->showNearbySessions($r->created_at, $username, $days);

                $sessions = \App\Models\RadAcct::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->whereBetween('acctstarttime', [$r->created_at->copy()->subDays($days), $r->created_at->copy()->addDays($days)])->get();
                if ($sessions->isNotEmpty()) {
                    $names = $sessions->pluck('nasidentifier')->filter()->unique()->implode(', ');
                    $ips = $sessions->pluck('nasipaddress')->filter()->unique()->implode(', ');
                    $this->line("  Candidate NAS identifiers: {$names}");
                    $this->line("  Candidate NAS IPs: {$ips}");
                }
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
