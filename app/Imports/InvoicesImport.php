<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class InvoicesImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Customers'     => new InvoiceCustomersSheetImport(),
            'Invoices'      => new InvoiceDetailsSheetImport(),
            'Invoice Items' => new InvoiceItemsSheetImport(),
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Customers
|--------------------------------------------------------------------------
*/

class InvoiceCustomersSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name'])) {
                continue;
            }

            $customer = null;

            if (!empty($row['gstin'])) {
                $customer = Customer::where(
                    'gstin',
                    $row['gstin']
                )->first();
            }

            if (!$customer && !empty($row['email'])) {
                $customer = Customer::where(
                    'email',
                    $row['email']
                )->first();
            }

            /*
             * For this migration workbook, customer name may be
             * the only available identifier.
             */
            if (!$customer) {
                $customer = Customer::where(
                    'name',
                    trim($row['name'])
                )->first();
            }

            if ($customer) {

                $customer->update([
                    'name' => trim($row['name']),
                    'email' => $row['email'] ?? $customer->email,
                    'phone' => $row['phone'] ?? $customer->phone,
                    'address' => $row['address'] ?? $customer->address,
                    'gstin' => $row['gstin'] ?? $customer->gstin,
                ]);

                continue;
            }

            Customer::create([
                'name' => trim($row['name']),
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'gstin' => $row['gstin'] ?? null,
            ]);
        }
    }
}


/*
|--------------------------------------------------------------------------
| Invoices
|--------------------------------------------------------------------------
*/

class InvoiceDetailsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (
                empty($row['invoice_number']) ||
                empty($row['customer'])
            ) {
                continue;
            }

            $invoiceNumber = trim($row['invoice_number']);

            /*
             * For now duplicate invoice = stop import.
             * Skip / Overwrite can be added later.
             */
            if (
                Invoice::where(
                    'invoice_number',
                    $invoiceNumber
                )->exists()
            ) {
                throw new \Exception(
                    "Invoice No. {$invoiceNumber} already exists."
                );
            }

            $customer = Customer::where(
                'name',
                trim($row['customer'])
            )->first();

            if (!$customer) {
                throw new \Exception(
                    "Customer {$row['customer']} not found."
                );
            }

            $date = $row['date'];

            if (is_numeric($date)) {
                $date = ExcelDate::excelToDateTimeObject($date)
                    ->format('Y-m-d');
            } else {
                $date = date(
                    'Y-m-d',
                    strtotime($date)
                );
            }

            Invoice::create([
                'invoice_number' => $invoiceNumber,
                'date' => $date,
                'customer_id' => $customer->id,

                // Recalculated after items are imported.
                'total_amount' => 0,
                'gst_amount' => 0,
                'grand_total' => 0,

                'status' => $row['status'] ?? 'Unpaid',
            ]);
        }
    }
}


/*
|--------------------------------------------------------------------------
| Invoice Items
|--------------------------------------------------------------------------
*/

class InvoiceItemsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $invoiceNumbers = [];

            foreach ($rows as $row) {

                if (
                    empty($row['invoice_number']) ||
                    empty($row['product']) ||
                    empty($row['quantity'])
                ) {
                    continue;
                }

                $invoiceNumber = trim($row['invoice_number']);
                $productName = trim($row['product']);

                $invoice = Invoice::where(
                    'invoice_number',
                    $invoiceNumber
                )->first();

                if (!$invoice) {
                    throw new \Exception(
                        "Invoice No. {$invoiceNumber} not found."
                    );
                }

                /*
                 * Products MUST already exist.
                 */
                $product = Product::where(
                    'name',
                    $productName
                )->first();

                if (!$product) {
                    throw new \Exception(
                        "Product {$productName} does not exist. Add or import the product before importing this invoice."
                    );
                }

                $quantity = (int) $row['quantity'];
                $mrp = (float) ($row['mrp'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $discount = (float) ($row['discount'] ?? 0);
                $gstRate = (float) ($row['gst_rate'] ?? 0);

                /*
                 * Keep values from the actual invoice export.
                 */
                $subtotal = (float) ($row['subtotal'] ?? 0);
                $taxAmount = (float) ($row['tax_amount'] ?? 0);
                $total = (float) ($row['total'] ?? 0);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'mrp' => $mrp,
                    'rate' => $rate,
                    'discount' => $discount,
                    'quantity' => $quantity,
                    'gst_rate' => $gstRate,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                ]);

                /*
                 * Invoice = STOCK OUT.
                 *
                 * Negative stock is intentionally allowed.
                 * Example:
                 * stock 10 - invoice 20 = -10
                 */
                StockMovement::record(
                    $product,
                    'sale',
                    -$quantity,
                    'invoice',
                    $invoice->id,
                    'Imported invoice ' . $invoiceNumber
                );

                $invoiceNumbers[] = $invoiceNumber;
            }

            /*
             * Calculate invoice totals from its imported items.
             */
            foreach (array_unique($invoiceNumbers) as $invoiceNumber) {

                $invoice = Invoice::where(
                    'invoice_number',
                    $invoiceNumber
                )->first();

                $invoice->update([
                    'total_amount' => $invoice->items()->sum('subtotal'),
                    'gst_amount' => $invoice->items()->sum('tax_amount'),
                    'grand_total' => $invoice->items()->sum('total'),
                ]);
            }
        });
    }
}