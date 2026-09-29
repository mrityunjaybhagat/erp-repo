<?php

namespace App\Imports;

use App\Models\Expense;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ExpensesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (
                empty($row['category']) ||
                empty($row['amount']) ||
                empty($row['date'])
            ) {
                continue;
            }

            Expense::create([
                'category'     => trim($row['category']),
                'vendor_name'  => $row['vendor_name'] ?? null,
                'description'  => $row['description'] ?? null,
                'amount'       => $row['amount'],
                'payment_mode' => $row['payment_mode'] ?? null,
                'date'         => $row['date'],
            ]);
        }
    }
}