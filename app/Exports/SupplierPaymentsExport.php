<?php

namespace App\Exports;

use App\Models\SupplierPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierPaymentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return SupplierPayment::with('purchase')
            ->get()
            ->map(function ($payment) {
                return [
                    'purchase_number' => $payment->purchase?->purchase_number,
                    'amount'          => $payment->amount,
                    'method'          => $payment->method,
                    'date'            => $payment->date,
                    'reference_note'  => $payment->reference_note,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'purchase_number',
            'amount',
            'method',
            'date',
            'reference_note',
        ];
    }
}