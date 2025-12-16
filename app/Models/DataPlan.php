<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPlan extends Model
{
    protected $fillable = [
        'name', 'days', 'data_limit', 'price', 
        'is_active', 'is_featured', 'sort_order'
    ];
}