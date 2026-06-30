<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    protected $fillable = [
        'user_id', 'lomba_id', 'team_name', 'team_members', 'status', 'notes', 'team_locked', 'unlock_requested',
    ];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
            'team_locked' => 'boolean',
            'unlock_requested' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lomba()
    {
        return $this->belongsTo(Lomba::class);
    }

    public function submission()
    {
        return $this->hasOne(Submission::class);
    }
}
