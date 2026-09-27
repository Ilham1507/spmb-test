<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanSpmb extends Model
{
    protected $table = 'pengaturan_spmb';
    protected $guarded = ['id'];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

}
