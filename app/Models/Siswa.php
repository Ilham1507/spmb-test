<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $fillable = ['nama', 'nis', 'kelas', 'jurusan', 'phone', 'email', 'user_id', 'form_data'];
    protected function casts(): array { return ['form_data' => 'array']; }

    public function user() { return $this->belongsTo(User::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
