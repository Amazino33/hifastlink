<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mac',
        'router_id',
        'ip',
        'user_agent',
        'first_seen',
        'last_seen',
        'is_connected',
        'meta',
    ];

    protected $casts = [
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'is_connected' => 'boolean',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Upsert device when a login/connect occurs.
     * - user: authenticated user model
     * - mac: device MAC
     * - routerIdentifier: nas_identifier or router id
     */
    public static function upsertFromLogin($user, $mac, $routerIdentifier = null, $ip = null, $userAgent = null, $meta = null)
    {
        if (! $user || ! $mac) {
            return null;
        }

        $routerId = null;
        if ($routerIdentifier) {
            $router = Router::where('nas_identifier', $routerIdentifier)->orWhere('id', $routerIdentifier)->first();
            $routerId = $router?->id;
        }

        $device = self::firstOrNew(['user_id' => $user->id, 'mac' => $mac]);
        $now = now();

        if (! $device->exists || ! $device->first_seen) {
            $device->first_seen = $device->first_seen ?: $now;
        }

        $device->ip = $ip ?: $device->ip;
        $device->user_agent = $userAgent ?: $device->user_agent;
        $device->router_id = $routerId ?: $device->router_id;
        $device->last_seen = $now;
        $device->is_connected = true;

        if ($meta) {
            $device->meta = array_merge($device->meta ?? [], (array) $meta);
        }

        $device->save();

        return $device;
    }
}
