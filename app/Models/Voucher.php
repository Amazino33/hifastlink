<?php
// app/Models/Voucher.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'plan_id',
        'created_by',
        'router_id',
        'duration_hours',
        'data_limit_mb',
        'max_uses',
        'used_count',
        'expires_at',
        'is_used',
        'used_by',
        'used_at',
    ];

    protected $casts = [
        'is_used'        => 'boolean',
        'used_at'        => 'datetime',
        'expires_at'     => 'datetime',
        'duration_hours' => 'integer',
        'data_limit_mb'  => 'integer',
        'max_uses'       => 'integer',
        'used_count'     => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    // ─── Static Helpers ───────────────────────────────────────────────

    /**
     * Detect if a string looks like a voucher code (VCH-XXXXXXXX).
     */
    public static function isVoucherCode(string $input): bool
    {
        return (bool) preg_match('/^VCH-[A-Z0-9]{8}$/i', trim($input));
    }

    /**
     * Generate a unique voucher code.
     */
    public static function generateCode(): string
    {
        do {
            $code = 'VCH-' . strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Find a valid, usable voucher by code.
     */
    public static function findValid(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereColumn('used_count', '<', 'max_uses')
            ->first();
    }

    /**
     * Mark voucher as consumed and register who used it.
     */
    public function consume(?int $userId = null): void
    {
        $this->increment('used_count');
        $this->refresh();

        $update = [];

        if ($this->used_count >= $this->max_uses) {
            $update['is_used'] = true;
            $update['used_by'] = $userId;
            $update['used_at'] = now();
        }

        if (!empty($update)) {
            $this->update($update);
        }
    }
}