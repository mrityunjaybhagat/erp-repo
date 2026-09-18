<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashVoucher extends Model
{
    use HasFactory;

    protected $fillable = ['voucher_number', 'type', 'party_name', 'amount', 'narration', 'payment_mode', 'date'];
}
