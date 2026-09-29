<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Purchase::with(['supplier', 'items.product'])
            ->get()
            ->flatMap(function ($purchase) {

                return $purchase->items->map(function ($item) use ($purchase) {

                    return [
                        'purchase_number' => $purchase->purchase_number,
                        'date'            => $purchase->date,
                        'supplier'        => $purchase->supplier?->name,
                        'product'         => $item->product?->name,
                        'quantity'        => $item->quantity,
                        'rate'            => $item->rate,
                        'gst_rate'        => number_format((float) $item->gst_rate, 2, '.', ''),
                        'subtotal'        => $item->subtotal,
                        'tax_amount'      => $item->tax_amount,
                        'total'           => $item->total,
                        'status'          => $purchase->status,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'purchase_number',
            'date',
            'supplier',
            'product',
            'quantity',
            'rate',
            'gst_rate',
            'subtotal',
            'tax_amount',
            'total',
            'status',
        ];
    }
}