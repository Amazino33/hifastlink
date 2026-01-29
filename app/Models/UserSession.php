<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'router_name',
        'ip_address',
        'mac_address',
        'profile',
        'session_timestamp',
        'bytes_in',
        'bytes_out',
        'used_bytes',
        'limit_bytes',
        'uptime',
    ];

    protected $casts = [
        'session_timestamp' => 'datetime',
        'bytes_in' => 'integer',
        'bytes_out' => 'integer',
        'used_bytes' => 'integer',
        'limit_bytes' => 'integer',
        'uptime' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}