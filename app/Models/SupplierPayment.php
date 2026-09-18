<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_id', 'amount', 'method', 'date', 'reference_note'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
