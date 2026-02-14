<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'ip_address',
        'nas_identifier',
        'secret',
        'api_user',
        'api_password',
        'api_port',
        'is_active',
        'description',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_port' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    // Virtual attribute: is_online (true if seen within last 5 minutes)
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->greaterThan(now()->subMinutes(5));
    }

    /**
     * Get active sessions for this router
     */
    public function activeSessions(): HasMany
    {
        return $this->hasMany(RadAcct::class, 'nasipaddress', 'ip_address')
            ->whereNull('acctstoptime');
    }

    /**
     * Get all sessions (active + historical) for this router
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(RadAcct::class, 'nasipaddress', 'ip_address');
    }

    /**
     * Get the NAS entry for this router
     */
    public function nasEntry()
    {
        return $this->hasOne(Nas::class, 'nasname', 'ip_address');
    }

    /**
     * Get active users count
     */
    public function getActiveUsersCountAttribute(): int
    {
        return $this->activeSessions()->distinct('username')->count('username');
    }

    /**
     * Get total bandwidth used today
     */
    public function getTodayBandwidthAttribute(): int
    {
        return $this->sessions()
            ->whereDate('acctstarttime', today())
            ->sum(\DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
    }

    /**
     * Get MAC plan assignments for this router.
     */
    public function macAssignments(): HasMany
    {
        return $this->hasMany(MacPlanAssignment::class);
    }

    /**
     * Scope for active routers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
