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
        'is_family',
        'family_limit',
        'allowed_login_time',
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
     * Get the speed limit attribute (combined upload/download).
     */
    protected function speedLimit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->speed_limit_download ? ($this->speed_limit_upload ?? 0) . 'k/' . $this->speed_limit_download . 'k' : null,
        );
    }
}
