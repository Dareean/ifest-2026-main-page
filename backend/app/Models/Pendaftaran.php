<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'lomba_id', 'team_name', 'status', 'gelombang', 'notes',
        'payment_proof', 'payment_status', 'payment_verified_at', 'payment_notes',
        'last_reminder_sent_at', 'team_locked', 'unlock_requested',
        'ig_follow_proof', 'ig_twibbon_proof', 'social_validated',
    ];

    protected $appends = ['max_members'];

    protected function casts(): array
    {
        return [
            'payment_verified_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function isFree(): bool
    {
        $fee = $this->lomba?->fee;
        if (!$fee) return false;
        $normalized = strtolower(trim($fee));
        return in_array($normalized, ['gratis', 'free', '0', 'rp 0', 'gratis (free)', 'no fee', 'n/a']);
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
