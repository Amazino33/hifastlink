<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadCheck extends Model
{
    protected $connection = 'radius';
    protected $table = 'radcheck';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $fillable = ['username', 'attribute', 'op', 'value'];
    public $timestamps = false;
}