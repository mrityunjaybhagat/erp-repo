<?php

namespace App\Imports;

use App\Models\UserType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserTypesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name'])) {
                continue;
            }

            UserType::firstOrCreate([
                'name' => trim($row['name']),
            ]);
        }
    }
}