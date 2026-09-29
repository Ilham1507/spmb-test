<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinatPromosi extends Model
{
    protected $table = 'minat_promosi';

    protected $guarded = ['id'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function jurusanDiminati()
    {
        return $this->belongsTo(Jurusan::class, 'interested_major_id');
    }
}
