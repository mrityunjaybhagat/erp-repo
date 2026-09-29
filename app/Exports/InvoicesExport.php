<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Invoice::with(['customer', 'items.product'])
            ->get()
            ->flatMap(function ($invoice) {

                return $invoice->items->map(function ($item) use ($invoice) {

                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'date'           => $invoice->date,
                        'customer'       => $invoice->customer?->name,
                        'product'        => $item->product?->name,
                        'mrp'            => $item->mrp,
                        'rate'           => $item->rate,
                        'discount'       => $item->discount,
                        'quantity'       => $item->quantity,
                        'gst_rate'       => number_format((float) $item->gst_rate, 2, '.', ''),
                        'subtotal'       => $item->subtotal,
                        'tax_amount'     => $item->tax_amount,
                        'total'          => $item->total,
                        'status'         => $invoice->status,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'invoice_number',
            'date',
            'customer',
            'product',
            'mrp',
            'rate',
            'discount',
            'quantity',
            'gst_rate',
            'subtotal',
            'tax_amount',
            'total',
            'status',
        ];
    }
}