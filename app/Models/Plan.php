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
        'data_limit',
        'limit_unit',
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

    protected static function booted()
    {
        static::saved(function ($plan) {
            // 1. Calculate the Bytes
            $bytes = 0;
            if ($plan->limit_unit === 'MB') {
                $bytes = $plan->data_limit * 1024 * 1024;
            } elseif ($plan->limit_unit === 'GB') {
                $bytes = $plan->data_limit * 1024 * 1024 * 1024;
            }

            // 2. Create/Update the Radius Rule (radgroupreply)
            if ($plan->limit_unit !== 'Unlimited') {
                \App\Models\RadGroupReply::updateOrCreate(
                    [
                        'groupname' => $plan->name, // The link (e.g., "Daily-1GB")
                        'attribute' => 'Mikrotik-Total-Limit'
                    ],
                    [
                        'op'    => ':=',
                        'value' => (string) $bytes // The long number (e.g., 1073741824)
                    ]
                );
            } else {
                // If Unlimited, remove any limits
                \App\Models\RadGroupReply::where('groupname', $plan->name)
                    ->where('attribute', 'Mikrotik-Total-Limit')
                    ->delete();
            }
        });
    }
}
