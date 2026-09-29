<?php

namespace App\Imports;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SuppliersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name'])) {
                continue;
            }

            $supplier = null;

            // First preference: GSTIN
            if (!empty($row['gstin'])) {
                $supplier = Supplier::where('gstin', $row['gstin'])->first();
            }

            // Second preference: Email
            if (!$supplier && !empty($row['email'])) {
                $supplier = Supplier::where('email', $row['email'])->first();
            }

            if ($supplier) {
                $supplier->update([
                    'name'    => $row['name'],
                    'email'   => $row['email'] ?? null,
                    'phone'   => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'gstin'   => $row['gstin'] ?? null,
                ]);

                continue;
            }

            Supplier::create([
                'name'    => $row['name'],
                'email'   => $row['email'] ?? null,
                'phone'   => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'gstin'   => $row['gstin'] ?? null,
            ]);
        }
    }
}