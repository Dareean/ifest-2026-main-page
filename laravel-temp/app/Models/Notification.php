<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'judul', 'pesan', 'is_read',
    ];

    protected static function booted()
    {
        static::created(function ($notification) {
            try {
                // Gunakan queue() bukan send() — agar API response tidak tertahan oleh email dispatch
                Mail::to($notification->user->email)->queue(new NotificationMail($notification));
            } catch (\Exception $e) {
                Log::error('Gagal mengantri email notifikasi ke ' . $notification->user->email . ': ' . $e->getMessage());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
