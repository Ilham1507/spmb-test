<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCheckout extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['selected_items' => 'array', 'production' => 'boolean', 'expires_at' => 'datetime', 'checked_at' => 'datetime'];

    public function bill() { return $this->belongsTo(TagihanPendaftar::class, 'bill_id'); }
}
