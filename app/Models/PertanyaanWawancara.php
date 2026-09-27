<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PertanyaanWawancara extends Model
{
    protected $table = 'pertanyaan_wawancara';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'boolean',
    ];
}
