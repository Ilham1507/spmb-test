<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['siswa_id', 'payment_type', 'method', 'amount', 'bank_account_id', 'proof_path', 'status', 'invoice_number', 'notes', 'paid_at', 'submitted_by', 'approved_by', 'approved_at', 'received_by', 'received_at'];
    protected function casts(): array { return ['paid_at' => 'datetime']; }
    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function details() { return $this->hasMany(PaymentDetail::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function treasurer() { return $this->belongsTo(User::class, 'received_by'); }
}
