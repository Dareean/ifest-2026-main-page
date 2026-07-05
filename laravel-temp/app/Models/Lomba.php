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
        'gelombang_1_start', 'gelombang_1_end', 'gelombang_2_end',
        'fee_gelombang_1', 'fee_gelombang_2',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'is_active' => 'boolean',
            'gelombang_1_start' => 'date',
            'gelombang_1_end' => 'date',
            'gelombang_2_end' => 'date',
        ];
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function getMaxMembers(): int
    {
        if (str_contains(strtolower($this->team_requirements), 'individu')) {
            return 1;
        }

        preg_match('/(\d+)\s*[-–]\s*(\d+)/', $this->team_requirements, $matches);
        if ($matches) {
            return (int) $matches[2];
        }

        preg_match('/(?:maxs?\.?|mak?s\.?)\s*(\d+)/i', $this->team_requirements, $matches);
        if ($matches) {
            return (int) $matches[1];
        }

        preg_match('/(\d+)\s*orang/i', $this->team_requirements, $matches);
        if ($matches) {
            return (int) $matches[1];
        }

        return 3;
    }
}
