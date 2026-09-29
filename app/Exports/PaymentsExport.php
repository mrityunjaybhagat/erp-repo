<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Payment::with('invoice')
            ->get()
            ->map(function ($payment) {
                return [
                    'invoice_number' => $payment->invoice?->invoice_number,
                    'amount'         => $payment->amount,
                    'method'         => $payment->method,
                    'date'           => $payment->date,
                    'reference_note' => $payment->reference_note,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'invoice_number',
            'amount',
            'method',
            'date',
            'reference_note',
        ];
    }
}