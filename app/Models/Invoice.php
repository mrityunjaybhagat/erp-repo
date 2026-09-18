<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_number', 'date', 'customer_id', 'total_amount', 'gst_amount', 'grand_total', 'status'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Matches your real confirmed shape: keyed by rate, nested totals.
    public function gstSummary(): array
    {
        $summary = [];
        foreach ($this->items as $item) {
            $rate = $item->gst_rate;
            $summary[$rate] ??= ['total_items' => 0, 'subtotal' => 0, 'total_tax_amount' => 0];
            $summary[$rate]['total_items']++;
            $summary[$rate]['subtotal'] += (float) $item->subtotal;
            $summary[$rate]['total_tax_amount'] += (float) $item->tax_amount;
        }
        return $summary;
    }

    // Matches your real confirmed shape: keyed by HSN code.
    public function hsnSummary(): array
    {
        $summary = [];
        foreach ($this->items as $item) {
            $code = $item->product->hsn_code ?? 'UNKNOWN';
            $summary[$code] ??= ['code' => $code, 'description' => '', 'uqc' => '', 'qty' => 0, 'total_value' => 0, 'taxable_value' => 0, 'igst_amt' => 0];
            $summary[$code]['qty'] += $item->quantity;
            $summary[$code]['total_value'] += (float) $item->subtotal;
            $summary[$code]['taxable_value'] += (float) $item->subtotal;
        }
        return $summary;
    }
}
