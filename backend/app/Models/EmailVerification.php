<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EmailVerification extends Model
{
    protected $fillable = [
        'email', 'otp', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>=', now());
    }

    public function setOtpAttribute($value): void
    {
        $this->attributes['otp'] = Hash::make($value);
    }

    public function verify(string $plainOtp): bool
    {
        return Hash::check($plainOtp, $this->otp);
    }
}
