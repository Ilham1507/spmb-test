<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiPembayaran extends Model
{
    protected $table = 'transaksi_pembayaran';
    protected $guarded = ['id'];
    protected $casts = ['selected_items' => 'array'];

    public function tagihan() { return $this->belongsTo(TagihanPendaftar::class, 'bill_id'); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function treasurerReceiver() { return $this->belongsTo(User::class, 'treasurer_received_by'); }
    public function checkout() { return $this->hasOne(PaymentCheckout::class, 'transaction_id'); }
}
