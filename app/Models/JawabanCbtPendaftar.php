<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JawabanCbtPendaftar extends Model
{
    protected $table = 'jawaban_cbt_pendaftar';
    protected $guarded = ['id'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];
}
