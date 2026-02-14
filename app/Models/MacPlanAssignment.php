<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MacPlanAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'nas_identifier',
        'router_id',
        'plan_id',
        'device_name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the router for this assignment.
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get the data plan for this assignment.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope to get active assignments only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find assignments by NAS identifier and router.
     */
    public static function findByNasAndRouter(string $nasIdentifier, int $routerId)
    {
        return static::where('nas_identifier', $nasIdentifier)
            ->where('router_id', $routerId)
            ->active()
            ->with('dataPlan')
            ->get();
    }
}