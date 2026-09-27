<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganMatchVerification extends Model
{
    protected $table = 'kunjungan_match_verifications';
    protected $guarded = ['id'];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function pendaftar() { return $this->belongsTo(Pendaftar::class, 'applicant_id'); }
    public function kunjungan() { return $this->belongsTo(KunjunganPendaftar::class, 'visit_id'); }
}
