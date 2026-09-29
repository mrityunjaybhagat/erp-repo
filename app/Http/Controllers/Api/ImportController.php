<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Imports\SuppliersImport;
use App\Imports\UsersImport;
use App\Imports\UserTypesImport;
use App\Imports\ExpensesImport;
use App\Imports\InvoicesImport;
use App\Imports\PurchasesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function customers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(
            new CustomersImport,
            $request->file('file')
        );

        return response()->json([
            'message' => 'Customers imported successfully.',
        ]);
    }
    public function suppliers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(
            new SuppliersImport,
            $request->file('file')
        );

        return response()->json([
            'message' => 'Suppliers imported successfully.',
        ]);
    }
    public function users(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new UsersImport();

        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Users imported successfully.',
            'created_users' => $import->createdUsers,
        ]);
    }
    public function userTypes(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    Excel::import(
        new UserTypesImport,
        $request->file('file')
    );

    return response()->json([
        'message' => 'User types imported successfully.',
    ]);
}

public function expenses(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls,csv',
    ]);

    Excel::import(
        new ExpensesImport,
        $request->file('file')
    );

    return response()->json([
        'message' => 'Expenses imported successfully.',
    ]);
}
public function purchases(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    try {

        Excel::import(
            new PurchasesImport(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Purchases imported successfully.',
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage(),
        ], 422);
    }
}

public function invoices(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls',
    ]);

    try {

        Excel::import(
            new InvoicesImport(),
            $request->file('file')
        );

        return response()->json([
            'message' => 'Invoices imported successfully.',
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage(),
        ], 422);
    }
}
}