<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontakPendaftar extends Model
{
    protected $table = 'kontak_pendaftar';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
        ];
    }

}
