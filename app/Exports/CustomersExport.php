<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Customer::select(
            'name',
            'email',
            'phone',
            'address',
            'gstin'
        )->get();
    }

    public function headings(): array
    {
        return [
            'name',
            'email',
            'phone',
            'address',
            'gstin',
        ];
    }
}