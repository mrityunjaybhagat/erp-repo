<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_number', 'date', 'supplier_id', 'total_amount', 'gst_amount', 'grand_total', 'status'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

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
}
