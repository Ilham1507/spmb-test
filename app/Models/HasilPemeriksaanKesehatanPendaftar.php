<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilPemeriksaanKesehatanPendaftar extends Model
{
    protected $table = 'hasil_pemeriksaan_kesehatan_pendaftar';
    protected $guarded = ['id'];

    public function item() { return $this->belongsTo(ItemPemeriksaanKesehatan::class, 'health_check_item_id'); }
}
