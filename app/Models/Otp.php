<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['phone', 'otp', 'expires_at', 'verified_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return ! is_null($this->verified_at);
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isVerified();
    }
}
