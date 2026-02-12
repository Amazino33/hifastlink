<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Router;
use App\Models\RadAcct;
use Illuminate\Support\Facades\Log;

class BackfillRouterRefs extends Command
{
    protected $signature = 'router:backfill-refs {--limit=500}';
    protected $description = 'Backfill router_id on transactions and payments by matching RadAcct sessions';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Backfilling transactions (limit={$limit})...");
        $txs = Transaction::whereNull('router_id')->limit($limit)->get();
        foreach ($txs as $tx) {
            $this->backfillForRecord($tx);
        }

        $this->info("Backfilling payments (limit={$limit})...");
        $pays = Payment::whereNull('router_id')->limit($limit)->get();
        foreach ($pays as $pay) {
            $this->backfillForRecord($pay);
        }

        $this->info('Done. Rerun until all records are processed.');
        return 0;
    }

    protected function backfillForRecord($record)
    {
        $user = $record->user ?? null;
        if (! $user || empty($user->username) || ! $record->created_at) {
            return;
        }

        $start = $record->created_at->subDays(1);
        $end = $record->created_at->addDays(1);

        $session = RadAcct::where('username', $user->username)
            ->whereBetween('acctstarttime', [$start, $end])
            ->orderByDesc('acctstarttime')
            ->first();

        if (! $session) {
            return;
        }

        // Try to find router
        $router = null;
        if (! empty($session->nasidentifier)) {
            $router = Router::where('nas_identifier', $session->nasidentifier)->first();
        }
        if (! $router && ! empty($session->nasipaddress)) {
            $router = Router::where('ip_address', $session->nasipaddress)->first();
        }

        if ($router) {
            $record->router_id = $router->id;
            $record->save();
            $this->info("Updated record id={$record->id} with router_id={$router->id}");
        }
    }
}
