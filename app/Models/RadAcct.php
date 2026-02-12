<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RadAcct extends Model
{
    // protected $connection = 'radius';
    protected $table = 'radacct';
    protected $fillable = [
        'username', 'acctsessionid', 'acctuniqueid', 'realm', 'nasipaddress',
        'nasportid', 'nasporttype', 'acctstarttime', 'acctstoptime',
        'acctsessiontime', 'acctauthentic', 'connectinfo_start', 'connectinfo_stop',
        'acctinputoctets', 'acctoutputoctets', 'calledstationid', 'callingstationid',
        'acctterminatecause', 'servicetype', 'framedprotocol', 'framedipaddress',
        'acctupdatetime', 'nas_identifier'
    ];
    public $timestamps = false;

    protected $casts = [
        'acctstarttime' => 'datetime',
        'acctstoptime' => 'datetime',
        'acctupdatetime' => 'datetime',
        'acctsessiontime' => 'integer',
        'acctinputoctets' => 'integer',
        'acctoutputoctets' => 'integer',
    ];

    /**
     * Get active sessions.
     */
    public function scopeActive(Builder $query): Builder
    {
        // Consider a session active if it has no stop time and either:
        // - the account update time is recent (last 5 minutes), OR
        // - the session start time is recent (last 60 minutes), OR
        // - acctupdatetime is null but acctstarttime is present
        return $query->whereNull('acctstoptime')
            ->where(function (Builder $q) {
                $q->where('acctupdatetime', '>=', now()->subMinutes(5))
                  ->orWhere('acctstarttime', '>=', now()->subHours(1))
                  ->orWhereNull('acctupdatetime');
            });
    }

    /**
     * Get sessions for a specific username (case-insensitive).
     */
    public function scopeForUser(Builder $query, ?string $username): Builder
    {
        if (empty($username)) {
            // If no username, return a query that finds nothing (instead of crashing)
            return $query->whereRaw('1 = 0');
        }

        // Use case-insensitive comparison for username
        return $query->whereRaw('LOWER(username) = ?', [strtolower($username)]);
    }

    /**
     * Get total data usage (upload + download).
     */
    public function getTotalDataUsageAttribute(): int
    {
        return ($this->acctinputoctets ?? 0) + ($this->acctoutputoctets ?? 0);
    }

    /**
     * Get formatted total data usage.
     */
    public function getFormattedTotalDataUsageAttribute(): string
    {
        return $this->formatBytes($this->total_data_usage);
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
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
     * Check if session is currently active.
     */
    public function isActive(): bool
    {
        return $this->acctstoptime === null;
    }
}