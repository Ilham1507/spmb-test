<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenPendaftar extends Model
{
    protected $table = 'dokumen_pendaftar';
    protected $guarded = ['id'];
    protected $casts = ['verified_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function jenisDokumen() { return $this->belongsTo(JenisDokumen::class, 'document_type_id'); }
    public function pendaftar() { return $this->belongsTo(Pendaftar::class, 'applicant_id'); }
}
