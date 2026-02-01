<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements \Illuminate\Contracts\Auth\MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

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
        'pending_plan_id',
        'pending_plan_purchased_at',
    ];

    /**
     * Plan relationship
     */
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    /**
     * Pending plan relationship
     */
    public function pendingPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Plan::class, 'pending_plan_id');
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
            'pending_plan_purchased_at' => 'datetime',
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
    public function getRemainingDataAttribute(): int
    {
        if (!$this->data_limit) {
            return 0;
        }

        return max(0, $this->data_limit - $this->data_used);
    }

    /**
     * Get data usage percentage.
     */
    public function getDataUsagePercentageAttribute(): float
    {
        if (!$this->data_limit || $this->data_limit === 0) {
            return 0;
        }

        return min(100, ($this->data_used / $this->data_limit) * 100);
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
        return $this->formatBytes($this->data_used);
    }

    /**
     * Get formatted data limit.
     */
    public function getFormattedDataLimitAttribute(): string
    {
        return $this->formatBytes($this->data_limit);
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
}