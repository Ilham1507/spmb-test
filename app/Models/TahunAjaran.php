<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';
    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean', 'is_archived' => 'boolean', 'archived_at' => 'datetime'];

    public function pendaftars() { return $this->hasMany(Pendaftar::class, 'academic_year_id'); }

}
