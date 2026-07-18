<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'type', 'name', 'logo_url', 'instagram_url',
        'description', 'tier_data', 'order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tier_data' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
