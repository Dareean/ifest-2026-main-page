<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    protected $fillable = [
        'user_id', 'lomba_id', 'team_name', 'team_members', 'status', 'gelombang', 'notes',
        'team_locked', 'unlock_requested', 'auto_lock_at',
        'payment_proof', 'payment_status', 'payment_verified_at', 'payment_notes',
        'last_reminder_sent_at',
    ];

    protected $appends = ['max_members'];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
            'team_locked' => 'boolean',
            'unlock_requested' => 'boolean',
            'auto_lock_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    public function isFree(): bool
    {
        $fee = $this->lomba?->fee;
        return $fee && strtolower(trim($fee)) === 'gratis';
    }

    public function getMaxMembersAttribute(): int
    {
        return $this->lomba?->getMaxMembers() ?? 1;
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

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }
}
