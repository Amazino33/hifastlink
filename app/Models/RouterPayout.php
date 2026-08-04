<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterPayout extends Model
{
    protected $fillable = [
        'router_id',
        'period_start',
        'period_end',
        'amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'amount'       => 'decimal:2',
        'paid_at'      => 'datetime',
    ];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public static function calculateRevenue(int $routerId, string $periodStart, string $periodEnd): float
    {
        return (float) Transaction::where('router_id', $routerId)
            ->where('gateway', 'paystack')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->sum('amount');
    }
}
