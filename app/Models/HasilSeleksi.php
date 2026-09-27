<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasilSeleksi extends Model
{
    protected $table = 'hasil_seleksi';
    protected $guarded = ['id'];

    protected $casts = [
        'announcement_date' => 'datetime',
        'decided_at' => 'datetime',
        'whatsapp_result_notified_at' => 'datetime',
    ];

    public function pendaftar() { return $this->belongsTo(Pendaftar::class, 'applicant_id'); }
    public function major() { return $this->belongsTo(Jurusan::class, 'major_id'); }
    public function decisionMaker() { return $this->belongsTo(User::class, 'decided_by'); }
}
