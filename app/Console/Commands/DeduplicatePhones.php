<?php

namespace App\Console\Commands;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicatePhones extends Command
{
    protected $signature   = 'users:dedup-phones {--dry-run : Preview duplicates without deleting}';
    protected $description = 'Merge duplicate user accounts created by the old phone-format bug';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Load all users that have a phone number
        $users = User::whereNotNull('phone')
            ->withTrashed()
            ->get(['id', 'phone', 'username', 'email', 'password',
                   'plan_id', 'plan_expiry', 'plan_started_at',
                   'wallet_balance', 'connection_status',
                   'created_at', 'deleted_at']);

        // Group by last 10 digits — the common key across all format variants
        $groups = $users->groupBy(fn ($u) => substr(preg_replace('/\D/', '', $u->phone ?? ''), -10))
            ->filter(fn ($g, $key) => $g->count() > 1 && strlen((string) $key) === 10);

        if ($groups->isEmpty()) {
            $this->info('No duplicate phone numbers found.');
            return 0;
        }

        $this->info("Found {$groups->count()} phone number(s) with duplicates.");
        $this->newLine();

        $totalDeleted = 0;

        foreach ($groups as $last10 => $group) {
            $this->line("<fg=yellow>Phone (last 10): {$last10}</>");

            // Score each user — higher = more valuable account to keep.
            // Profile completeness always outweighs plan data, because plans are
            // transferable — we move the best plan to the kept account after scoring.
            $scored = $group->map(function (User $u) {
                $paymentCount = DB::table('payments')->where('user_id', $u->id)->count();
                $score = 0;

                // Profile completeness — worth more than any plan (plans get transferred)
                $score += $u->email    ? 100 : 0;
                $score += $u->password ? 100 : 0;
                $score += (! preg_match('/^user_\d{10}$/', $u->username ?? '')) ? 80 : 0;

                // Subscription — secondary weight since plan will be moved if needed
                $score += $u->plan_id                  ?  50 : 0;
                $score += $u->plan_expiry?->isFuture() ?  30 : 0;
                $score += $paymentCount                *  10;

                // Prefer active (not soft-deleted)
                $score += $u->deleted_at ? 0 : 10;

                return ['user' => $u, 'score' => $score, 'payments' => $paymentCount];
            // Tiebreaker: prefer older account (lower id) for stable ordering
            })->sortBy([['score', 'desc'], ['user.id', 'asc']])->values();

            $keep    = $scored->first()['user'];
            $discard = $scored->slice(1)->pluck('user');

            foreach ($scored as $row) {
                $u    = $row['user'];
                $tag  = $u->id === $keep->id ? '<fg=green>[KEEP]   </>' : '<fg=red>[DELETE]</>';
                $has  = implode(' ', array_filter([
                    $u->email    ? 'email'    : '',
                    $u->password ? 'password' : '',
                    (! preg_match('/^user_\d{10}$/', $u->username ?? '')) ? 'username' : '',
                    $u->plan_id  ? 'plan'     : '',
                ]));
                $this->line("  {$tag} id={$u->id}  phone={$u->phone}  score={$row['score']}  has=[{$has}]  payments={$row['payments']}");
            }

            // ── Decide what to transfer ───────────────────────────────

            // Best active plan anywhere in the group
            $allUsers  = $scored->pluck('user');
            $bestPlan  = $allUsers
                ->filter(fn ($u) => $u->plan_id && $u->plan_expiry?->isFuture())
                ->sortByDesc(fn ($u) => $u->plan_expiry->timestamp)
                ->first();

            $planTransfer = ($bestPlan && $bestPlan->id !== $keep->id) ? $bestPlan : null;

            // Sum every wallet balance in the group
            $totalWallet  = $allUsers->sum(fn ($u) => (float) ($u->wallet_balance ?? 0));
            $walletChange = $totalWallet > (float) ($keep->wallet_balance ?? 0);

            if ($planTransfer) {
                $this->line("  <fg=cyan>↳ Plan transfer:</> plan_id={$planTransfer->plan_id}  expires={$planTransfer->plan_expiry}  from id={$planTransfer->id}");
            }
            if ($walletChange) {
                $this->line("  <fg=cyan>↳ Wallet merge:</> ₦{$totalWallet} combined from {$allUsers->count()} account(s)");
            }

            if (! $dryRun) {
                DB::transaction(function () use ($keep, $discard, $planTransfer, $totalWallet, $walletChange) {
                    // 1. Transfer plan to kept account if a discard had a better one
                    if ($planTransfer) {
                        $keep->updateQuietly([
                            'plan_id'           => $planTransfer->plan_id,
                            'plan_expiry'       => $planTransfer->plan_expiry,
                            'plan_started_at'   => $planTransfer->plan_started_at,
                            'connection_status' => $planTransfer->connection_status ?? 'active',
                        ]);
                    }

                    // 2. Merge wallet balances
                    if ($walletChange) {
                        $keep->updateQuietly(['wallet_balance' => $totalWallet]);
                    }

                    // 3. Reassign child records to the kept account, then delete duplicates.
                    //    Tables with user_id FK to users must be re-pointed before forceDelete().
                    foreach ($discard as $u) {
                        foreach ([
                            'payments', 'transactions', 'devices',
                            'custom_plan_requests', 'pending_subscriptions',
                            'mac_plan_assignments', 'user_sessions',
                        ] as $table) {
                            DB::table($table)
                                ->where('user_id', $u->id)
                                ->update(['user_id' => $keep->id]);
                        }

                        // Vouchers may use creator_id instead of user_id
                        DB::table('vouchers')->where('user_id',    $u->id)->update(['user_id'    => $keep->id]);
                        DB::table('vouchers')->where('creator_id', $u->id)->update(['creator_id' => $keep->id]);

                        RadCheck::where('username', $u->username)->delete();
                        RadReply::where('username', $u->username)->delete();
                        $u->forceDelete();
                    }

                    // 4. Normalize kept account's phone to canonical +234... format
                    $canonical = User::normalizePhone($keep->getRawOriginal('phone'));
                    if ($keep->getRawOriginal('phone') !== $canonical) {
                        $keep->phone = $canonical;
                        $keep->saveQuietly();
                    }
                });

                $totalDeleted += $discard->count();
                $this->line("  → Done. Deleted {$discard->count()} duplicate(s). Keeping id={$keep->id}.");
            } else {
                $this->line("  → [DRY RUN] Would delete {$discard->count()} duplicate(s), keep id={$keep->id}.");
                $totalDeleted += $discard->count();
            }

            $this->newLine();
        }

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$totalDeleted} duplicate account(s) total.");

        return 0;
    }
}
