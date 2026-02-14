<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'radius_password',
        'username',
        'data_plan_id',
        'data_used',
        'data_limit',
        'online_status',
        'plan_expiry',
        'plan_started_at',
        'simultaneous_sessions',
        'subscription_start_date',
        'subscription_end_date',
        'last_online',
        'connection_status',
        'current_ip',
        'plan_id',
        'parent_id',
        'is_family_admin',
        'family_limit',
        'rollover_available_bytes',
        'rollover_validity_days',
        'router_id',
    ];

    /**
     * Plan relationship
     */
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    /**
     * Router relationship
     */
    public function router(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Router::class);
    }

    /**
     * Pending subscriptions relationship
     */
    public function pendingSubscriptions()
    {
        return $this->hasMany(PendingSubscription::class)->orderBy('created_at', 'asc');
    }

    /**
     * Family relationships
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'online_status' => 'boolean',
            'plan_expiry' => 'datetime',
            'simultaneous_sessions' => 'integer',
            'subscription_start_date' => 'datetime',
            'subscription_end_date' => 'datetime',
            'last_online' => 'datetime',
            'data_used' => 'integer',
            'data_limit' => 'integer',
            'is_family_admin' => 'boolean',
            'family_limit' => 'integer',
            'rollover_available_bytes' => 'integer',
            'rollover_validity_days' => 'integer',
        ];
    }

    /**
     * Get the data plan for this user.
     */
    public function dataPlan(): BelongsTo
    {
        return $this->belongsTo(DataPlan::class);
    }

    /**
     * Get the RADIUS accounting records for this user.
     */
    public function radAccts()
    {
        return $this->hasMany(RadAcct::class, 'username', 'username');
    }

    /**
     * Check if user's subscription is active.
     */
    public function isSubscriptionActive(): bool
    {
        if (!$this->subscription_end_date) {
            return false;
        }

        return $this->subscription_end_date->isFuture();
    }

    /**
     * Check if user has exceeded data limit.
     */
    public function hasExceededDataLimit(): bool
    {
        if (!$this->data_limit || $this->data_limit === 0) {
            return false;
        }

        return $this->data_used >= $this->data_limit;
    }

    /**
     * Get remaining data in bytes.
     */
    /**
     * Convert stored data value to bytes using heuristic.
     * - null => null
     * - small numbers (<= 1,048,576) are treated as MB and converted
     * - larger numbers are assumed to be bytes already
     */
    protected function storedValueToBytes($value): ?int
    {
        if (is_null($value)) {
            return null;
        }

        $v = (int) $value;

        if ($v === 0) {
            return 0;
        }

        // Heuristic: values <= 1 MB (1048576) are likely MB counts (e.g., 100 => 100 MB)
        if ($v <= 1048576) {
            return $v * 1048576;
        }

        // Otherwise assume already bytes
        return $v;
    }

    public function getRemainingDataAttribute(): int
    {
        $limitBytes = $this->storedValueToBytes($this->data_limit);
        $usedBytes = $this->storedValueToBytes($this->data_used) ?? (int) $this->data_used;

        if (!$limitBytes) {
            return 0;
        }

        return max(0, $limitBytes - $usedBytes);
    }

    /**
     * Get data usage percentage.
     */
    public function getDataUsagePercentageAttribute(): float
    {
        $limitBytes = $this->storedValueToBytes($this->data_limit);
        $usedBytes = $this->storedValueToBytes($this->data_used) ?? (int) $this->data_used;

        if (!$limitBytes || $limitBytes === 0) {
            return 0;
        }

        return min(100, ($usedBytes / $limitBytes) * 100);
    }

    /**
     * Friendly display status for the user's subscription.
     * Returns 'PLAN EXPIRED' when data remaining is 0 or less.
     */
    public function getDisplayStatusAttribute(): string
    {
        try {
            if ($this->remaining_data <= 0 && $this->plan_id) {
                return 'PLAN EXPIRED';
            }
        } catch (\Exception $e) {
            // ignore and fallthrough
        }

        return 'OK';
    }

    /**
     * Return the current plan considering data exhaustion and queued subscriptions.
     */
    public function getCurrentPlanAttribute()
    {
        // First, prefer user's active plan if it has remaining data or is unlimited
        if ($this->plan) {
            $limitBytes = $this->storedValueToBytes($this->plan->data_limit);
            $remaining = $this->remaining_data ?? 0;

            if (is_null($limitBytes) || $remaining > 0) {
                return $this->plan;
            }
        }

        // Next, check pending subscriptions (first in queue) - they should be active if current plan exhausted
        $pending = $this->pendingSubscriptions()->with('plan')->orderBy('created_at', 'asc')->first();
        if ($pending && $pending->plan) {
            return $pending->plan;
        }

        // Fallback to stored plan (even if exhausted) so something is always returned (nullable)
        return $this->plan;
    }

    /**
     * Return a simple status for the current plan: 'active', 'exhausted', 'inactive'
     */
    public function getCurrentPlanStatusAttribute(): string
    {
        $plan = $this->current_plan;
        if (! $plan) return 'inactive';

        $limitBytes = $this->storedValueToBytes($plan->data_limit);
        $remaining = $this->remaining_data ?? 0;

        if (is_null($limitBytes)) {
            return 'active'; // Unlimited
        }

        if ($remaining <= 0) {
            return 'exhausted';
        }

        return 'active';
    }

    /**
     * Format bytes to human readable format.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Get formatted data used.
     */
    public function getFormattedDataUsedAttribute(): string
    {
        $usedBytes = $this->storedValueToBytes($this->data_used) ?? (int) $this->data_used;
        return $this->formatBytes($usedBytes);
    }

    /**
     * Get human-readable plan validity.
     */
    public function getPlanValidityHumanAttribute(): string
    {
        if (!$this->plan_expiry) {
            return 'No Active Plan';
        }

        $diff = now()->diffInMinutes($this->plan_expiry, false);

        if ($diff < 0) {
            return 'Expired';
        }

        if ($diff < 60) {
            return $diff . ' Minutes';
        }

        if ($diff < 1440) {
            return ceil($diff / 60) . ' Hours';
        }

        return ceil($diff / 1440) . ' Days';
    }

    /**
     * Get formatted remaining data.
     */
    public function getFormattedRemainingDataAttribute(): string
    {
        return $this->formatBytes($this->remaining_data);
    }

    /**
     * Calculate rollover data for a new plan (same validity only).
     */
    public function calculateRolloverFor(Plan $newPlan): int
    {
        // If no current plan or different validity, no rollover.
        if (!$this->plan || $this->plan->validity_days != $newPlan->validity_days) {
            return 0;
        }

        // Return remaining data (ensure not negative)
        return max(0, $this->data_limit - $this->data_used);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Always let the "Boss" email in (The Master Key)
        if ($this->email === 'amazino33@gmail.com') {
            return true;
        }

        // 2. For everyone else, check if they are active or have a role
        // (Adjust this logic later for your staff)
        return $this->hasRole('super_admin') || $this->hasRole('cashier');
    }

    /**
     * The "booted" method of the model.
     * Automates creating/updating the Radius user.
     */
    protected static function booted()
    {
        // 1. When a User is CREATED -> Create a RadCheck entry
        static::created(function ($user) {
            // Only proceed if they have a username
            if (!empty($user->username)) {
                \App\Models\RadCheck::create([
                    'username'  => $user->username,
                    'attribute' => 'Cleartext-Password',
                    'op'        => ':=',
                    'value'     => $user->radius_password ?? '123456', // Default if empty
                ]);
                
                // Add Simultaneous-Use limit
                $maxDevices = ($user->plan && $user->plan->max_devices) ? $user->plan->max_devices : 1;
                \App\Models\RadCheck::create([
                    'username'  => $user->username,
                    'attribute' => 'Simultaneous-Use',
                    'op'        => ':=',
                    'value'     => (string) $maxDevices,
                ]);
            }
        });

        // 2. When a User is UPDATED -> Update Radius password if changed
        static::updated(function ($user) {
            if ($user->isDirty('radius_password') && !empty($user->username)) {
                \App\Models\RadCheck::where('username', $user->username)
                    ->where('attribute', 'Cleartext-Password')
                    ->update(['value' => $user->radius_password]);
            }
            
            // If plan_id changed, sync RADIUS attributes
            if ($user->isDirty('plan_id') && !empty($user->username)) {
                // Update or create Simultaneous-Use based on new plan
                $maxDevices = ($user->plan && $user->plan->max_devices) ? $user->plan->max_devices : 1;
                \App\Models\RadCheck::updateOrCreate(
                    [
                        'username' => $user->username,
                        'attribute' => 'Simultaneous-Use',
                    ],
                    [
                        'op' => ':=',
                        'value' => (string) $maxDevices,
                    ]
                );
                
                // Trigger full plan sync to update all RADIUS attributes
                \App\Services\PlanSyncService::syncUserPlan($user);
            }
        });
    }

    /**
     * Human readable data limit.
     *
     * Heuristic:
     * - null => Unlimited
     * - small numeric values (<= 1,048,576) are treated as MB and multiplied to bytes
     * - otherwise treated as bytes
     */
    public function getDataLimitHumanAttribute(): string
    {
        if (is_null($this->data_limit)) {
            return 'Unlimited';
        }

        $val = (int) $this->data_limit;

        if ($val <= 1048576) {
            // most likely stored as MB (e.g. 100 => 100 MB)
            $bytes = $val * 1048576;
        } else {
            // already bytes
            $bytes = $val;
        }

        return Number::fileSize($bytes);
    }

    /**
     * Human readable remaining data (data_limit - data_used).
     * Uses the same heuristic as getDataLimitHumanAttribute.
     */
    public function getDataRemainingHumanAttribute(): string
    {
        if (is_null($this->data_limit)) {
            return 'Unlimited';
        }

        $limitVal = (int) $this->data_limit;
        $usedVal = (int) $this->data_used;

        // convert both to bytes using same heuristic
        $limitBytes = $limitVal <= 1048576 ? $limitVal * 1048576 : $limitVal;
        $usedBytes  = $usedVal  <= 1048576 ? $usedVal  * 1048576 : $usedVal;

        $remaining = max(0, $limitBytes - $usedBytes);

        return Number::fileSize($remaining);
    }

    /**
     * Get the router the user is currently connected to.
     */
    public function getCurrentRouter()
    {
        // Check active RADIUS session to determine current router
        $activeSession = $this->radAccts()
            ->whereNull('acctstoptime')
            ->latest('acctstarttime')
            ->first();

        if ($activeSession && $activeSession->nasipaddress) {
            return Router::where('ip_address', $activeSession->nasipaddress)->first();
        }

        return null;
    }
}
