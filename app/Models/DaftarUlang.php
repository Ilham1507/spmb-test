<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarUlang extends Model
{
    protected $table = 'daftar_ulang';
    protected $guarded = ['id'];

    public function pendaftar() { return $this->belongsTo(Pendaftar::class, 'applicant_id'); }
}
