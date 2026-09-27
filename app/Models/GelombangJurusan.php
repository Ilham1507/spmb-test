<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GelombangJurusan extends Model
{
    protected $table = 'gelombang_jurusan';
    protected $guarded = ['id'];

    protected $casts = ['biaya_masuk' => 'float', 'rincian_biaya' => 'array'];

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }
}
