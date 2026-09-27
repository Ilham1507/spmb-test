<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilUkurSeragamPendaftar extends Model
{
    protected $table = 'hasil_ukur_seragam_pendaftar';
    protected $guarded = ['id'];

    public function size() { return $this->belongsTo(UkuranSeragam::class, 'uniform_size_id'); }
}
