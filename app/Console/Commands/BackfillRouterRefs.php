<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Router;
use App\Models\RadAcct;
use Illuminate\Support\Facades\DB;

class BackfillRouterRefs extends Command
{
    protected $signature = 'router:backfill-refs {--limit=500} {--days=7} {--loop}';
    protected $description = 'Backfill router_id on transactions and payments by matching RadAcct sessions (improved heuristics)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $days = (int) $this->option('days');
        $loop = (bool) $this->option('loop');

        $totalUpdated = 0;
        do {
            $updated = 0;

            $this->info("Backfilling transactions (limit={$limit}, days=±{$days})...");
            $txs = Transaction::whereNull('router_id')->limit($limit)->get();
            foreach ($txs as $tx) {
                $updated += $this->backfillForRecord($tx, $days);
            }

            $this->info("Backfilling payments (limit={$limit}, days=±{$days})...");
            $pays = Payment::whereNull('router_id')->limit($limit)->get();
            foreach ($pays as $pay) {
                $updated += $this->backfillForRecord($pay, $days);
            }

            $totalUpdated += $updated;
            $this->info("Batch updated: {$updated} records. Total updated: {$totalUpdated}");

            if (! $loop) break;

            // Continue looping until a batch makes no progress
        } while ($updated > 0);

        $this->info('Done. If any records remain null, consider increasing --days or reviewing unmatched rows.');
        return 0;
    }

    protected function backfillForRecord($record, int $days = 7): int
    {
        $user = $record->user ?? null;
        if (! $user || empty($user->username) || ! $record->created_at) {
            return 0;
        }

        $start = $record->created_at->copy()->subDays($days);
        $end = $record->created_at->copy()->addDays($days);

        $usernameLower = mb_strtolower($user->username);

        $session = RadAcct::whereRaw('LOWER(username) = ?', [$usernameLower])
            ->whereBetween('acctstarttime', [$start, $end])
            ->orderByDesc('acctstarttime')
            ->first();

        if (! $session) {
            // As a fallback, try matching by framedipaddress or callingstationid in case username mismatch
            $session = RadAcct::whereBetween('acctstarttime', [$start, $end])
                ->where(function($q) use ($record) {
                    if (!empty($record->user_id)) {
                        $u = \App\Models\User::find($record->user_id);
                        if ($u && $u->username) {
                            $q->where('username', $u->username)
                              ->orWhere('framedipaddress', $u->current_ip ?? '');
                        }
                    }
                })->orderByDesc('acctstarttime')->first();
        }

        if (! $session) {
            return 0;
        }

        $router = null;

        // Try nasidentifier (exact)
        if (! empty($session->nasidentifier)) {
            $router = Router::where('nas_identifier', $session->nasidentifier)->first();
        }

        // Try ip
        if (! $router && ! empty($session->nasipaddress)) {
            $router = Router::where('ip_address', $session->nasipaddress)->first();
        }

        // Try NAS shortname (if nas table exists)
        if (! $router && ! empty($session->nasipaddress)) {
            $nas = DB::table('nas')->where('nasname', $session->nasipaddress)->first();
            if ($nas && ! empty($nas->shortname)) {
                $router = Router::where('nas_identifier', $nas->shortname)->first();
            }
        }

        if ($router) {
            $record->router_id = $router->id;
            $record->save();
            $this->info("Updated {$record->getTable()} id={$record->id} with router_id={$router->id}");
            return 1;
        }

        return 0;
    }
}
