<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDataWarning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public float $usagePercent;

    public function __construct(User $user, float $usagePercent)
    {
        $this->user = $user;
        $this->usagePercent = $usagePercent;
    }

    public function handle(): void
    {
        // Send email notification
        $this->user->notify(new \App\Notifications\DataUsageWarning($this->usagePercent));

        // Send SMS if phone number exists
        if ($this->user->phone) {
            // Integrate with SMS service
            Log::info('SMS data warning sent', [
                'user' => $this->user->username,
                'phone' => $this->user->phone,
                'usage' => $this->usagePercent
            ]);
        }

        Log::info('Data usage warning sent', [
            'user' => $this->user->username,
            'usage_percent' => $this->usagePercent
        ]);
    }
}