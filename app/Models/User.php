<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'pengguna';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role() { return $this->belongsTo(Peran::class, 'role_id'); }
    public function pendaftar() { return $this->hasOne(Pendaftar::class); }
    public function kunjunganDiterima() { return $this->hasMany(KunjunganPendaftar::class, 'received_by'); }
    public function pembayaranDiverifikasi() { return $this->hasMany(TransaksiPembayaran::class, 'verified_by'); }
    public function loginHistory() { return $this->hasMany(RiwayatLogin::class, 'pengguna_id'); }
    public function phoneChangeVerification() { return $this->hasOne(VerifikasiPerubahanWhatsapp::class); }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }
}
