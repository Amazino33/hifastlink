<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\RadGroupReply;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'data_limit',
        'time_limit',
        'speed_limit_upload',
        'speed_limit_download',
        'validity_days',
        'duration_days',
        'speed_limit',
        'is_family',
        'family_limit',
        'allowed_login_time',
        'limit_unit',
        'max_devices',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
        'router_id',
        'is_custom',
        'show_universal_plans',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_family' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_custom' => 'boolean',
        'show_universal_plans' => 'boolean',
        'features' => 'array',
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
            // Remove entries for old name if the plan was renamed
            $originalName = $plan->getOriginal('name');
            if ($originalName && $originalName !== $plan->name) {
                RadGroupReply::where('groupname', $originalName)->delete();
            }

            // Remove existing radgroupreply entries for this plan to avoid duplicates
            RadGroupReply::where('groupname', $plan->name)->delete();

            $attributes = [];

            // Data limit -> Mikrotik-Total-Limit (bytes)
            if ($plan->limit_unit !== 'Unlimited' && $plan->data_limit) {
                if ($plan->limit_unit === 'GB') {
                    $bytes = (int) ($plan->data_limit * 1073741824);
                } else { // MB
                    $bytes = (int) ($plan->data_limit * 1048576);
                }

                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Mikrotik-Total-Limit',
                    'op' => ':=',
                    'value' => (string) $bytes,
                ];
            }

            // Rate limit -> Mikrotik-Rate-Limit (uploadk/downloadk)
            if ($plan->speed_limit_upload || $plan->speed_limit_download) {
                $upload = $plan->speed_limit_upload ? (int) $plan->speed_limit_upload : 0;
                $download = $plan->speed_limit_download ? (int) $plan->speed_limit_download : 0;
                // Use k suffix (as existing UI expects Kbps)
                $rate = "{$upload}k/{$download}k";

                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $rate,
                ];
            }

            // Session timeout -> Session-Timeout (seconds)
            if ($plan->time_limit) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Session-Timeout',
                    'op' => ':=',
                    'value' => (string) ((int) $plan->time_limit),
                ];
            }

            // Login-time restriction -> Login-Time (raw string from select)
            if ($plan->allowed_login_time) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Login-Time',
                    'op' => ':=',
                    'value' => $plan->allowed_login_time,
                ];
            }

            // Device limit -> Simultaneous-Use (max concurrent sessions)
            $maxDevices = $plan->max_devices ?? 1;
            $attributes[] = [
                'groupname' => $plan->name,
                'attribute' => 'Simultaneous-Use',
                'op' => ':=',
                'value' => (string) $maxDevices,
            ];

            // Validity (optional) -> Acct-Interim-Interval (or other attribute as needed)
            if ($plan->validity_days) {
                $attributes[] = [
                    'groupname' => $plan->name,
                    'attribute' => 'Acct-Interim-Interval',
                    'op' => ':=',
                    'value' => (string) ($plan->validity_days * 86400),
                ];
            }

            // Insert all attributes
            foreach ($attributes as $attr) {
                try {
                    RadGroupReply::create($attr);
                } catch (\Exception $e) {
                    Log::error("Failed to sync RadGroupReply for plan {$plan->name}: " . $e->getMessage(), ['attr' => $attr]);
                }
            }

            // Also update all users who have this plan assigned
            // Update their individual radcheck Simultaneous-Use entry
            if ($plan->wasChanged('max_devices') || $plan->wasRecentlyCreated) {
                $users = \App\Models\User::where('plan_id', $plan->id)->get();
                foreach ($users as $user) {
                    if ($user->username) {
                        // Update or create Simultaneous-Use in radcheck for this user
                        $maxDevices = $plan->max_devices ?? 1;
                        \App\Models\RadCheck::updateOrCreate(
                            [
                                'username' => $user->username,
                                'attribute' => 'Simultaneous-Use',
                            ],
                            [
                                'op' => ':=',
                                'value' => (string) $maxDevices,
                            ]
                        );
                    }
                }
                Log::info("Updated Simultaneous-Use for " . count($users) . " users on plan {$plan->name}");
            }
        });

        // Remove all radgroupreply entries when a plan is deleted
        static::deleting(function ($plan) {
            RadGroupReply::where('groupname', $plan->name)->delete();
        });
    } 
 
    public function getDataLimitHumanAttribute(): string
    {
        if ($this->limit_unit === 'Unlimited' || ! $this->data_limit) {
            return 'Unlimited';
        }

        $bytes = $this->limit_unit === 'GB'
            ? (int) ($this->data_limit * 1073741824)
            : (int) ($this->data_limit * 1048576);

        return Number::fileSize($bytes);
    }
} 