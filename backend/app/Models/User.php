<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar', 'phone', 'institution', 'age', 'instagram_username', 'google_token', 'google_refresh_token'])]
#[Hidden(['password', 'remember_token', 'google_token', 'google_refresh_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = strip_tags($value);
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $value ? strip_tags($value) : null;
    }

    public function setInstitutionAttribute($value): void
    {
        $this->attributes['institution'] = $value ? strip_tags($value) : null;
    }

    public function setInstagramUsernameAttribute($value): void
    {
        $this->attributes['instagram_username'] = $value ? strip_tags($value) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function submissions()
    {
        return $this->hasManyThrough(Submission::class, Pendaftaran::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return config('app.frontend_url') . '/reset-password/' . $token . '?email=' . $notifiable->email;
        });
        $this->notify(new ResetPassword($token));
    }
}
