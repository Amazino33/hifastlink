<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VoucherBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'user_id',
        'plan_id',
        'router_id',
        'quantity',
        'unit_price',
        'total_cost',
        'payment_method',
        'transaction_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity'   => 'integer',
    ];

    public static function generateBatchCode(): string
    {
        do {
            $code = 'VB-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (static::where('batch_code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }

    public function getRedeemedCountAttribute(): int
    {
        return $this->vouchers()->where('used_count', '>', 0)->count();
    }

    public function getUnredeemedCountAttribute(): int
    {
        return $this->vouchers()->where('used_count', 0)->count();
    }

    public function getRevenueRealizedAttribute(): float
    {
        return (float) ($this->redeemed_count * (float) $this->unit_price);
    }
}
