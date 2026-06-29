<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lomba extends Model
{
    protected $fillable = [
        'kode', 'title', 'scale', 'tagline', 'fee', 'target',
        'team_requirements', 'languages', 'babak', 'description',
        'long_description', 'rules', 'schedule', 'registration_link',
        'guidebook_link', 'card_bg', 'accent_color', 'text_color', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class);
    }
}
