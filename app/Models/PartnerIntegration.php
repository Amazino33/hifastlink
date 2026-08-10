<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerIntegration extends Model
{
    protected $fillable = [
        'name', 'slug', 'api_url', 'api_key',
        'code_field', 'primary_color', 'icon',
        'portal_title', 'portal_subtitle',
        'code_label', 'code_placeholder', 'code_maxlength',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'code_maxlength' => 'integer',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }
}
