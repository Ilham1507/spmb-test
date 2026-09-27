<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';
    protected $guarded = ['id'];
    protected $casts = [
        'tampil_mulai' => 'datetime',
        'tampil_sampai' => 'datetime',
        'gallery' => 'array',
        'content_blocks' => 'array',
    ];

}
