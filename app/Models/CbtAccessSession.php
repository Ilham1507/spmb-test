<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbtAccessSession extends Model
{
    protected $table = 'cbt_access_sessions';
    protected $guarded = ['id'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function pendaftar()
    {
        return $this->belongsTo(Pendaftar::class, 'applicant_id');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open'
            && blank($this->closed_at)
            && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
