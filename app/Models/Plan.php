<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'data_limit',
        'time_limit',
        'speed_limit_upload',
        'speed_limit_download',
        'validity_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get users assigned to this plan.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Accessor & mutator for data_limit: stored in bytes but represented in MB in UI.
     * NOTE: Using factor 10241024 per request (mutator multiplies by 10241024; accessor divides by 10241024).
     */
    protected function dataLimit(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => is_null($value) ? null : intdiv((int) $value, 10241024),
            set: fn ($value) => is_null($value) ? null : ((int) $value * 10241024),
        );
    }
}
