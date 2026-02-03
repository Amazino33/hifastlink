<?php

namespace App\Jobs;

use App\Models\RadCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RestoreRadCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $username;
    public $oldValue;

    public function __construct(string $username, $oldValue)
    {
        $this->username = $username;
        $this->oldValue = $oldValue;
    }

    public function handle()
    {
        try {
            if ($this->oldValue === null) {
                // If no old value, delete the radcheck row
                RadCheck::where('username', $this->username)->delete();
            } else {
                RadCheck::updateOrCreate([
                    'username' => $this->username,
                ], [
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $this->oldValue,
                ]);
            }
            Log::info("RestoreRadCheck: restored for {$this->username}");
        } catch (\Exception $e) {
            Log::error("RestoreRadCheck failed for {$this->username}: " . $e->getMessage());
        }
    }
}
