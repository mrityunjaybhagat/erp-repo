<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\CustomersExport;
use App\Exports\SuppliersExport;
use App\Exports\ProductsExport;
use App\Exports\UsersExport;
use App\Exports\PurchasesExport;
use App\Exports\InvoicesExport;
use App\Exports\ExpensesExport;
use App\Exports\PaymentsExport;
use App\Exports\SupplierPaymentsExport;

use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function customers()
    {
        return Excel::download(
            new CustomersExport,
            'customers.xlsx'
        );
    }

    public function suppliers()
    {
        return Excel::download(
            new SuppliersExport,
            'suppliers.xlsx'
        );
    }
    public function products()
    {
        return Excel::download(
            new ProductsExport,
            'products.xlsx'
        );
    }

    public function users()
    {
        return Excel::download(
            new UsersExport,
            'users.xlsx'
        );
    }
    public function purchases()
    {
        return Excel::download(
            new PurchasesExport,
            'purchases.xlsx'
        );
    }
    public function invoices()
    {
        return Excel::download(
            new InvoicesExport,
            'invoices.xlsx'
        );
    }
    public function expenses()
    {
        return Excel::download(
            new ExpensesExport,
            'expenses.xlsx'
        );
    }
    public function payments()
{
    return Excel::download(
        new PaymentsExport,
        'payments.xlsx'
    );
}

public function supplierPayments()
{
    return Excel::download(
        new SupplierPaymentsExport,
        'supplier-payments.xlsx'
    );
}
}