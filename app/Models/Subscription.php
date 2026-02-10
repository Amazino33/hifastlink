<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'starts_at',
        'expires_at',
        'data_limit',
        'data_remaining',
        'auto_renew',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Scope: active subscriptions (status ACTIVE and not expired).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope: filter by user id.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if subscription currently has data remaining (or unlimited).
     */
    public function hasDataRemaining(): bool
    {
        if (is_null($this->data_limit) || is_null($this->data_remaining)) {
            // Treat null as unlimited
            return true;
        }

        return $this->data_remaining > 0;
    }

    /**
     * Check if subscription is currently active and valid.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'ACTIVE') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
