<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'pendaftaran_id', 'link_drive', 'link_figma', 'originality_statement', 'catatan', 'status',
    ];

    public function setCatatanAttribute($value): void
    {
        $this->attributes['catatan'] = $value ? strip_tags($value) : null;
    }

    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
