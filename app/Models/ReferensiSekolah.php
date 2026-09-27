<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferensiSekolah extends Model
{
    protected $table = 'referensi_sekolah';

    protected $guarded = ['id'];

    public function getAlamatLengkapAttribute(): string
    {
        return collect([
            $this->alamat,
            $this->desa_kelurahan,
            $this->kecamatan,
            $this->kabupaten_kota,
            $this->provinsi,
        ])->filter()->implode(', ');
    }
}
