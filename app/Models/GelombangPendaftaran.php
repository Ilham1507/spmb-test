<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GelombangPendaftaran extends Model
{
    protected $table = 'gelombang_pendaftaran';
    protected $guarded = ['id'];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'academic_year_id');
    }

    public function jurusanBiaya()
    {
        return $this->hasMany(GelombangJurusan::class, 'gelombang_id');
    }
}
