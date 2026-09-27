<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaTes extends Model
{
    protected $table = 'peserta_tes';
    protected $guarded = ['id'];

    public function tes(): BelongsTo
    {
        return $this->belongsTo(TesMasuk::class, 'test_id');
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'applicant_id');
    }
}
