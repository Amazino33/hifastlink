<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadUserGroup extends Model
{
    use HasFactory;

    // Radius tables don't typically use 'created_at' and 'updated_at'
    public $timestamps = false;

    // The exact table name in the database
    protected $table = 'radusergroup';

    // The columns we are allowed to write to
    protected $fillable = [
        'username',
        'groupname',
        'priority',
    ];
}