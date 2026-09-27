<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditPembayaran extends Model
{
    protected $table = 'audit_pembayaran';
    protected $guarded = ['id'];

}
