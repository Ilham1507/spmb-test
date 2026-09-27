<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatStatusPendaftar extends Model
{
    protected $table = 'riwayat_status_pendaftar';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];

    public function editor() { return $this->belongsTo(User::class, 'diubah_oleh'); }

}
