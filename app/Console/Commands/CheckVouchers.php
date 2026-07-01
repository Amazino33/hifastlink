<?php

namespace App\Console\Commands;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\Voucher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckVouchers extends Command
{
    protected $signature   = 'vouchers:cleanup {--dry-run : List what would be deleted without deleting}';
    protected $description = 'Delete expired and plan-orphaned vouchers and free up slots';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $total  = 0;

        // ── 1. Vouchers whose own expires_at is in the past ────────────────
        $expired = Voucher::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $this->info("Found {$expired->count()} expired vouchers (own expiry).");
        $total += $this->deleteVouchers($expired, $dryRun);

        // ── 2. Creator-based vouchers where creator's plan has expired ──────
        // Get all family head user IDs whose plan_expiry is in the past
        $expiredCreators = User::whereNotNull('plan_expiry')
            ->where('plan_expiry', '<', now())
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($expiredCreators->isNotEmpty()) {
            $creatorExpired = Voucher::whereIn('created_by', $expiredCreators)
                ->whereNull('expires_at') // own expiry not set — tied to creator's plan
                ->get();

            $this->info("Found {$creatorExpired->count()} vouchers tied to expired creator plans.");
            $total += $this->deleteVouchers($creatorExpired, $dryRun);
        }

        // ── 3. Fully redeemed vouchers older than 30 days ──────────────────
        $fullyUsed = Voucher::whereColumn('used_count', '>=', 'max_uses')
            ->where('used_at', '<', now()->subDays(30))
            ->get();

        $this->info("Found {$fullyUsed->count()} fully-redeemed vouchers older than 30 days.");
        $total += $this->deleteVouchers($fullyUsed, $dryRun);

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$total} voucher(s) total.");

        return 0;
    }

    private function deleteVouchers($vouchers, bool $dryRun): int
    {
        $count = $vouchers->count();

        if ($count === 0) return 0;

        if ($dryRun) {
            foreach ($vouchers as $v) {
                $this->line("  [DRY RUN] Would delete: {$v->code} (created_by: {$v->created_by})");
            }
            return $count;
        }

        foreach ($vouchers as $v) {
            RadCheck::where('username', $v->code)->delete();
            RadReply::where('username', $v->code)->delete();
            $v->delete();
        }

        return $count;
    }
}
