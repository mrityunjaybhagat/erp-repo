<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    public array $createdUsers = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name']) || empty($row['email'])) {
                continue;
            }

            // Find or create User Type
            $userType = null;

            if (!empty($row['user_type'])) {
                $userType = UserType::firstOrCreate([
                    'name' => trim($row['user_type']),
                ]);
            }

            // Find existing user
            $user = User::where('email', trim($row['email']))->first();

            $data = [
                'name'         => $row['name'],
                'phone'        => $row['phone'] ?? null,
                'user_type_id' => $userType?->id,
                'is_active'    =>
                    strtolower(trim($row['is_active'] ?? 'active')) === 'active',
            ];

            // Existing user: update without touching password
            if ($user) {
                $user->update($data);
                continue;
            }

            // New user: generate temporary password
            $temporaryPassword = Str::password(12);

            $user = User::create([
                ...$data,
                'email'    => trim($row['email']),
                'password' => $temporaryPassword,
            ]);

            $this->createdUsers[] = [
                'name'               => $user->name,
                'email'              => $user->email,
                'temporary_password' => $temporaryPassword,
            ];
        }
    }
}