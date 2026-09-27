<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifikasiPerubahanWhatsapp extends Model
{
    protected $table = 'verifikasi_perubahan_whatsapp';
    protected $guarded = ['id'];
    protected $casts = ['expires_at' => 'datetime', 'last_sent_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
