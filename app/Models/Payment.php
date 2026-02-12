<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'plan_name',
        'status',
        'router_id',
    ];

    public function router()
    {
        return $this->belongsTo(\App\Models\Router::class, 'router_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}