<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpensesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Expense::select(
            'category',
            'vendor_name',
            'description',
            'amount',
            'payment_mode',
            'date'
        )->get();
    }

    public function headings(): array
    {
        return [
            'category',
            'vendor_name',
            'description',
            'amount',
            'payment_mode',
            'date',
        ];
    }
}