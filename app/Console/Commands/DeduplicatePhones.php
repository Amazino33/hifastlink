<?php

namespace App\Console\Commands;

use App\Models\RadCheck;
use App\Models\RadReply;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
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
            ->get(['id', 'phone', 'username', 'plan_id', 'plan_expiry', 'created_at', 'deleted_at']);

        // Group by last 10 digits — the common key across all format variants
        $groups = $users->groupBy(fn ($u) => substr(preg_replace('/\D/', '', $u->phone ?? ''), -10))
            ->filter(fn ($g) => $g->count() > 1 && strlen($g->keys()->first()) === 10);

        if ($groups->isEmpty()) {
            $this->info('No duplicate phone numbers found.');
            return 0;
        }

        $this->info("Found {$groups->count()} phone number(s) with duplicates.");
        $this->newLine();

        $totalDeleted = 0;

        foreach ($groups as $last10 => $group) {
            $this->line("<fg=yellow>Phone (last 10): {$last10}</>");

            // Score each user — higher = more valuable account to keep
            $scored = $group->map(function (User $u) {
                $paymentCount = DB::table('payments')->where('user_id', $u->id)->count();
                $score = 0;
                $score += $u->plan_id               ? 100 : 0;
                $score += $u->plan_expiry?->isFuture() ? 50  : 0;
                $score += $paymentCount              * 20;
                $score += $u->deleted_at             ? 0   : 10; // prefer non-deleted
                return ['user' => $u, 'score' => $score, 'payments' => $paymentCount];
            })->sortByDesc('score');

            $keep    = $scored->first()['user'];
            $discard = $scored->slice(1)->pluck('user');

            foreach ($scored as $row) {
                $u   = $row['user'];
                $tag = $u->id === $keep->id ? '<fg=green>[KEEP]   </>' : '<fg=red>[DELETE]</>';
                $this->line("  {$tag} id={$u->id}  phone={$u->phone}  username={$u->username}  plan_id=" . ($u->plan_id ?? 'none') . "  payments={$row['payments']}");
            }

            if (! $dryRun) {
                foreach ($discard as $u) {
                    RadCheck::where('username', $u->username)->delete();
                    RadReply::where('username', $u->username)->delete();
                    $u->forceDelete(); // permanent — they're duplicate shells
                    $totalDeleted++;
                }
                $this->line("  → Deleted {$discard->count()} duplicate(s).");
            } else {
                $this->line("  → [DRY RUN] Would delete {$discard->count()} duplicate(s).");
                $totalDeleted += $discard->count();
            }

            $this->newLine();
        }

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$totalDeleted} duplicate account(s) total.");

        return 0;
    }
}
