<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'level', 'type', 'message', 'context',
        'url', 'method', 'user_id', 'ip', 'user_agent', 'occurred_at',
    ];

    protected $casts = [
        'context'     => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Capture a Throwable into the system_logs table.
     * Safe to call from inside exception handlers — any DB failure is silently swallowed.
     */
    public static function capture(\Throwable $e, string $level = 'error', array $extra = []): void
    {
        try {
            $request = request();

            static::create([
                'level'       => $level,
                'type'        => get_class($e),
                'message'     => $e->getMessage(),
                'context'     => array_merge([
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'trace' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 20)),
                ], $extra),
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'user_id'     => auth()->id(),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable) {
            // Never let logging crash the app
        }
    }

    /**
     * Log a plain message (no exception).
     */
    public static function log(string $message, string $level = 'info', array $context = []): void
    {
        try {
            $request = request();

            static::create([
                'level'       => $level,
                'type'        => 'manual',
                'message'     => $message,
                'context'     => $context,
                'url'         => $request->fullUrl(),
                'method'      => $request->method(),
                'user_id'     => auth()->id(),
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable) {
            //
        }
    }

    public function scopeErrors($query)
    {
        return $query->where('level', 'error');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function levelColor(): string
    {
        return match ($this->level) {
            'error'   => 'red',
            'warning' => 'orange',
            'info'    => 'blue',
            default   => 'gray',
        };
    }

    public function shortType(): string
    {
        if (! $this->type || $this->type === 'manual') return 'Manual';
        return class_basename($this->type);
    }
}
