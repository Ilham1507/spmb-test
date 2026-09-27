<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanPendaftar extends Model
{
    protected $table = 'tagihan_pendaftar';
    protected $guarded = ['id'];

    protected $casts = ['rincian_biaya' => 'array'];

    public function pendaftar() { return $this->belongsTo(Pendaftar::class, 'applicant_id'); }
    public function jenisTagihan() { return $this->belongsTo(JenisTagihan::class, 'bill_type_id'); }
    public function transaksi() { return $this->hasMany(TransaksiPembayaran::class, 'bill_id'); }
    public function checkouts() { return $this->hasMany(PaymentCheckout::class, 'bill_id'); }

    public function hasActiveCheckout(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('payment_checkouts')
            && $this->checkouts()->whereNotNull('active_bill_id')->exists();
    }
}
