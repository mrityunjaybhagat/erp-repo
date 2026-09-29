<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // Name is required in our customers table
            if (empty($row['name'])) {
                continue;
            }

            $customer = null;

            // First priority: GSTIN
            if (!empty($row['gstin'])) {
                $customer = Customer::where('gstin', $row['gstin'])->first();
            }

            // Second priority: Email
            if (!$customer && !empty($row['email'])) {
                $customer = Customer::where('email', $row['email'])->first();
            }

            // Existing customer found -> update
            if ($customer) {
                $customer->update([
                    'name'    => $row['name'],
                    'email'   => $row['email'] ?? null,
                    'phone'   => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'gstin'   => $row['gstin'] ?? null,
                ]);

                continue;
            }

            // No duplicate -> create new customer
            Customer::create([
                'name'    => $row['name'],
                'email'   => $row['email'] ?? null,
                'phone'   => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'gstin'   => $row['gstin'] ?? null,
            ]);
        }
    }
}