<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'data_limit',
        'validity_days',
        'price',
        'speed_limit',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
        'router_id',
        'is_custom',
        'show_universal_plans',
    ];

    protected $casts = [
        'data_limit' => 'integer',
        'validity_days' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'is_custom' => 'boolean',
        'show_universal_plans' => 'boolean',
    ];

    // Helper methods
    public function getFormattedDataLimitAttribute()
    {
        $bytes = $this->data_limit;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeUniversal($query)
    {
        return $query->where('is_custom', false);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopeForRouter($query, $routerId)
    {
        return $query->where(function ($q) use ($routerId) {
            $q->where('is_custom', false) // Universal plans
              ->orWhere(function ($subQuery) use ($routerId) {
                  $subQuery->where('is_custom', true)
                           ->where('router_id', $routerId);
              });
        });
    }

    /**
     * Get users subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the router this custom plan belongs to.
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Get MAC address assignments for this plan.
     */
    public function macAssignments(): HasMany
    {
        return $this->hasMany(MacPlanAssignment::class);
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHoursAttribute(): int
    {
        return $this->validity_days * 24;
    }

    /**
     * Get duration in seconds (for RADIUS Session-Timeout).
     */
    public function getDurationSecondsAttribute(): int
    {
        return $this->validity_days * 24 * 3600;
    }
}