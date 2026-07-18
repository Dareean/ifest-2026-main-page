<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineEvent extends Model
{
    protected $fillable = [
        'phase', 'title', 'date_range', 'description_items',
        'accent_color', 'status', 'order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'description_items' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
