<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
   public function collection()
{
    return User::with('userType')
        ->get()
        ->map(function ($user) {
            return [
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'user_type' => $user->userType?->name,
                'is_active' => $user->is_active ? 'Active' : 'Inactive',
            ];
        });
}

public function headings(): array
{
    return [
        'name',
        'email',
        'phone',
        'user_type',
        'is_active',
    ];
}
}