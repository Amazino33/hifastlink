<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Transaction;

class RouterPayout extends Model
{
    protected $fillable = [
        'router_id',
        'period_start',
        'period_end',
        'amount',
        'status',
        'paid_at',
        'denied_reason',
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

    /**
     * Sum completed Paystack transactions for a router in a period,
     * excluding the router owner's own subscription payments.
     */
    public static function calculateRevenue(
        int $routerId,
        string $periodStart,
        string $periodEnd,
        ?int $excludeUserId = null
    ): float {
        return (float) Transaction::where('router_id', $routerId)
            ->where('gateway', 'paystack')
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$periodStart . ' 00:00:00', $periodEnd . ' 23:59:59'])
            ->when($excludeUserId, fn ($q) => $q->where('user_id', '!=', $excludeUserId))
            ->sum('amount');
    }

    /**
     * Auto-generate pending payout records for all owned routers in a given month.
     * Skips routers that already have a record for that period, and skips ₦0 revenue.
     * Returns the number of records created.
     */
    public static function generateForMonth(int $year, int $month): int
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start)); // last day of month

        $routers = Router::with('owner')
            ->where('is_active', true)
            ->whereNotNull('owner_id')
            ->get();

        $created = 0;

        foreach ($routers as $router) {
            // Skip if a payout already exists for this router + period
            $exists = static::where('router_id', $router->id)
                ->where('period_start', $start)
                ->where('period_end', $end)
                ->exists();

            if ($exists) {
                continue;
            }

            $amount = static::calculateRevenue(
                $router->id,
                $start,
                $end,
                $router->owner_id // exclude owner's own payments
            );

            if ($amount <= 0) {
                continue;
            }

            static::create([
                'router_id'    => $router->id,
                'period_start' => $start,
                'period_end'   => $end,
                'amount'       => $amount,
                'status'       => 'pending',
            ]);

            $created++;
        }

        return $created;
    }
}
