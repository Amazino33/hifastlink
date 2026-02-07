<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\RadGroupReply;
use Illuminate\Console\Command;

class SyncPlanAttributes extends Command
{
    protected $signature = 'radius:sync-plan-attributes';
    protected $description = 'Sync all plan attributes including Simultaneous-Use to RadGroupReply';

    public function handle()
    {
        $this->info('Syncing plan attributes to RadGroupReply...');

        $plans = Plan::all();
        
        if ($plans->isEmpty()) {
            $this->warn('No plans found to sync.');
            return Command::SUCCESS;
        }

        $synced = 0;

        foreach ($plans as $plan) {
            $this->line("Processing plan: {$plan->name}");
            
            // Remove existing entries for this plan
            RadGroupReply::where('groupname', $plan->name)->delete();

            $attributes = [];

            // Data limit
            if ($plan->limit_unit !== 'Unlimited' && $plan->data_limit) {
                if ($plan->limit_unit === 'GB') {
                    $bytes = (int) ($plan->data_limit * 1073741824);
                } else {
                    $bytes = (int) ($plan->data_limit * 1048576);
                }

                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Mikrotik-Total-Limit',
                    'op' => ':=',
                    'value' => (string) $bytes,
                ];
            }

            // Rate limit
            if ($plan->speed_limit_upload || $plan->speed_limit_download) {
                $upload = $plan->speed_limit_upload ? (int) $plan->speed_limit_upload : 0;
                $download = $plan->speed_limit_download ? (int) $plan->speed_limit_download : 0;
                $rate = "{$upload}k/{$download}k";

                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $rate,
                ];
            }

            // Session timeout
            if ($plan->time_limit) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Session-Timeout',
                    'op' => ':=',
                    'value' => (string) ((int) $plan->time_limit),
                ];
            }

            // Login-time restriction
            if ($plan->allowed_login_time) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Login-Time',
                    'op' => ':=',
                    'value' => $plan->allowed_login_time,
                ];
            }

            // Simultaneous-Use (Device limit)
            $maxDevices = $plan->max_devices ?? 1;
            $attributes[] = [
                'groupname' => $plan->name,
                'attribute' => 'Simultaneous-Use',
                'op' => ':=',
                'value' => (string) $maxDevices,
            ];

            // Validity days
            if ($plan->validity_days) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Acct-Interim-Interval',
                    'op' => ':=',
                    'value' => (string) ($plan->validity_days * 86400),
                ];
            }

            // Insert all attributes
            foreach ($attributes as $attr) {
                try {
                    RadGroupReply::create($attr);
                    $this->line("  ✓ Added {$attr['attribute']}: {$attr['value']}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to add {$attr['attribute']}: " . $e->getMessage());
                }
            }

            $synced++;
        }

        $this->newLine();
        $this->info("Sync complete! Processed {$synced} plan(s).");
        $this->info("Check Admin Panel → Plan Definition (Rad Group Replies) to verify.");

        return Command::SUCCESS;
    }
}
