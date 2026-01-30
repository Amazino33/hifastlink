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
        'duration_days',
        'price',
        'speed_limit',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
    ];

    protected $casts = [
        'data_limit' => 'integer',
        'duration_days' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
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

    /**
     * Get users subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHoursAttribute(): int
    {
        return $this->duration_days * 24;
    }

    /**
     * Get duration in seconds (for RADIUS Session-Timeout).
     */
    public function getDurationSecondsAttribute(): int
    {
        return $this->duration_days * 24 * 3600;
    }
}