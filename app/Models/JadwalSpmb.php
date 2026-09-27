<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalSpmb extends Model
{
    protected $table = 'jadwal_spmb';
    protected $guarded = ['id'];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function getActivityAttribute(): ?string
    {
        return $this->kegiatan;
    }
}
