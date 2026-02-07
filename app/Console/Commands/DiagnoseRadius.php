<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\RadCheck;
use App\Models\RadGroupReply;
use App\Models\RadUserGroup;
use App\Models\RadAcct;
use Illuminate\Console\Command;

class DiagnoseRadius extends Command
{
    protected $signature = 'radius:diagnose {username}';
    protected $description = 'Diagnose RADIUS configuration for a specific user';

    public function handle()
    {
        $username = $this->argument('username');
        
        $this->info("=== RADIUS Diagnostic for: {$username} ===");
        $this->newLine();

        // 1. Check User
        $user = User::where('username', $username)->first();
        if (!$user) {
            $this->error("❌ User '{$username}' not found in users table!");
            return Command::FAILURE;
        }

        $this->info("✓ User found: {$user->name} (ID: {$user->id})");
        $this->line("  Email: {$user->email}");
        $this->line("  Plan ID: " . ($user->plan_id ?? 'None'));
        $this->newLine();

        // 2. Check Plan
        if ($user->plan) {
            $this->info("✓ Plan: {$user->plan->name}");
            $this->line("  Max Devices: " . ($user->plan->max_devices ?? 1));
            $this->line("  Data Limit: {$user->plan->data_limit} {$user->plan->limit_unit}");
            $this->line("  Speed: {$user->plan->speed_limit_upload}k / {$user->plan->speed_limit_download}k");
        } else {
            $this->warn("⚠ No plan assigned to this user");
        }
        $this->newLine();

        // 3. Check RadCheck
        $this->info("=== RadCheck Entries ===");
        $radCheckEntries = RadCheck::where('username', $username)->get();
        if ($radCheckEntries->isEmpty()) {
            $this->error("❌ No RadCheck entries found!");
        } else {
            foreach ($radCheckEntries as $entry) {
                $icon = $entry->attribute === 'Simultaneous-Use' ? '🔑' : '  ';
                $this->line("{$icon} {$entry->attribute} {$entry->op} {$entry->value}");
            }
        }
        $this->newLine();

        // 4. Check RadUserGroup
        $this->info("=== RadUserGroup Entries ===");
        $radUserGroup = RadUserGroup::where('username', $username)->first();
        if (!$radUserGroup) {
            $this->error("❌ User not assigned to any RADIUS group!");
            $this->line("   This is critical - MikroTik won't read group attributes without this!");
        } else {
            $this->line("✓ Group: {$radUserGroup->groupname} (Priority: {$radUserGroup->priority})");
        }
        $this->newLine();

        // 5. Check RadGroupReply (if user is in a group)
        if ($radUserGroup) {
            $this->info("=== RadGroupReply for '{$radUserGroup->groupname}' ===");
            $groupReplies = RadGroupReply::where('groupname', $radUserGroup->groupname)->get();
            if ($groupReplies->isEmpty()) {
                $this->warn("⚠ No RadGroupReply entries for this group!");
            } else {
                foreach ($groupReplies as $reply) {
                    $icon = $reply->attribute === 'Simultaneous-Use' ? '🔑' : '  ';
                    $this->line("{$icon} {$reply->attribute} {$reply->op} {$reply->value}");
                }
            }
        }
        $this->newLine();

        // 6. Check Active Sessions
        $this->info("=== Active RADIUS Sessions ===");
        $activeSessions = RadAcct::where('username', $username)
            ->whereNull('acctstoptime')
            ->get();
        
        if ($activeSessions->isEmpty()) {
            $this->line("No active sessions");
        } else {
            foreach ($activeSessions as $session) {
                $this->line("Session ID: {$session->acctsessionid}");
                $this->line("  IP: {$session->framedipaddress}");
                $this->line("  MAC: {$session->callingstationid}");
                $this->line("  Started: {$session->acctstarttime}");
                $this->line("  Upload: " . number_format($session->acctinputoctets / 1048576, 2) . " MB");
                $this->line("  Download: " . number_format($session->acctoutputoctets / 1048576, 2) . " MB");
                $this->newLine();
            }
            $this->info("Total Active Sessions: " . $activeSessions->count());
        }
        $this->newLine();

        // 7. Summary and Recommendations
        $this->info("=== Summary ===");
        $issues = [];
        
        if (!$radCheckEntries->where('attribute', 'Cleartext-Password')->first()) {
            $issues[] = "Missing Cleartext-Password in RadCheck";
        }
        
        if (!$radCheckEntries->where('attribute', 'Simultaneous-Use')->first()) {
            $issues[] = "Missing Simultaneous-Use in RadCheck";
        }
        
        if (!$radUserGroup) {
            $issues[] = "User not assigned to RADIUS group (RadUserGroup)";
        }
        
        if ($radUserGroup && $groupReplies->isEmpty()) {
            $issues[] = "No attributes defined for group '{$radUserGroup->groupname}'";
        }
        
        if ($radUserGroup && !$groupReplies->where('attribute', 'Simultaneous-Use')->first()) {
            $issues[] = "Missing Simultaneous-Use in RadGroupReply for group";
        }

        if (empty($issues)) {
            $this->info("✓ All checks passed! Configuration looks correct.");
            $this->newLine();
            $this->info("If multi-device still doesn't work, check:");
            $this->line("1. MikroTik: /ip hotspot profile print detail");
            $this->line("   - shared-users should be >= max_devices");
            $this->line("   - use-radius=yes");
            $this->line("2. Clear localStorage on both devices");
            $this->line("3. Check MikroTik logs: /log print where topics~\"hotspot\"");
        } else {
            $this->error("⚠ Issues found:");
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            $this->newLine();
            $this->info("Fix by running:");
            $this->line("  php artisan radius:sync-simultaneous-use");
            $this->line("  php artisan radius:sync-plan-attributes");
        }

        return Command::SUCCESS;
    }
}
