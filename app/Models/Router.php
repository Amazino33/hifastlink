<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Router extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'ip_address',
        'vpn_ip',
        'wireguard_public_key',
        'nas_identifier',
        'secret',
        'api_user',
        'api_password',
        'api_port',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_port' => 'integer',
        'last_seen_at' => 'datetime',
    ];



    /**
     * Boot method to auto-assign VPN IP
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($router) {
            if (empty($router->vpn_ip)) {
                $router->vpn_ip = self::getNextAvailableVpnIp();
            }
        });
    }

    protected static function booted()
    {
        static::deleted(function (Router $router) {
            
            // Only attempt to remove if the router actually had a WireGuard key
            if (!empty($router->wireguard_public_key)) {
                
                // 1. Instantly sever the connection in live memory (No downtime for others)
                $removePeer = Process::run("sudo wg set wg0 peer '{$router->wireguard_public_key}' remove");
                
                if ($removePeer->successful()) {
                    
                    // 2. Save the new state permanently so it doesn't return on reboot
                    Process::run("sudo wg-quick save wg0");
                    
                    Log::info("Successfully removed WireGuard peer for deleted router: {$router->name}");
                    
                } else {
                    // Log the Linux error if something goes wrong
                    Log::error("Failed to remove WireGuard peer for {$router->name}: " . $removePeer->errorOutput());
                }
            }
        });
    }

    /**
     * Get the next available VPN IP in the range
     */
    public static function getNextAvailableVpnIp(): string
    {
        $startIp = config('services.wireguard.start_ip', 10);
        $baseNetwork = '192.168.42.';

        // Get the highest assigned IP
        $lastRouter = self::whereNotNull('vpn_ip')
            ->orderByRaw('INET_ATON(vpn_ip) DESC')
            ->first();

        if (!$lastRouter) {
            // No routers yet, start from beginning
            return $baseNetwork . $startIp;
        }

        // Extract last octet and increment
        $lastIp = $lastRouter->vpn_ip;
        $lastOctet = (int) substr($lastIp, strrpos($lastIp, '.') + 1);
        $nextOctet = $lastOctet + 1;

        // Safety check: don't exceed 254
        if ($nextOctet > 254) {
            throw new \Exception('VPN IP pool exhausted. Maximum 245 routers supported.');
        }

        return $baseNetwork . $nextOctet;
    }

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
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));
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
