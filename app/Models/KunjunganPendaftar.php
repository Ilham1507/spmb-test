<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganPendaftar extends Model
{
    protected $table = 'kunjungan_pendaftar';
    protected $guarded = ['id'];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function pendaftar()
    {
        return $this->belongsTo(Pendaftar::class, 'applicant_id');
    }

    public function penerima()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function referensiSekolah()
    {
        return $this->belongsTo(ReferensiSekolah::class, 'school_reference_id');
    }

    public function jurusanDiminati()
    {
        return $this->belongsTo(Jurusan::class, 'interested_major_id');
    }

    public function matchVerifications()
    {
        return $this->hasMany(KunjunganMatchVerification::class, 'visit_id');
    }
}
