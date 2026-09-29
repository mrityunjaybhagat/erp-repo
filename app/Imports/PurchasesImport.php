<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PurchasesImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Suppliers'      => new PurchaseSuppliersSheetImport(),
            'Products'       => new PurchaseProductsSheetImport(),
            'Purchases'      => new PurchaseDetailsSheetImport(),
            'Purchase Items' => new PurchaseItemsSheetImport(),
        ];
    }
}


/*
|--------------------------------------------------------------------------
| Suppliers Sheet
|--------------------------------------------------------------------------
*/

class PurchaseSuppliersSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name'])) {
                continue;
            }

            $supplier = null;

            // Same matching logic as our Supplier import:
            // GSTIN first, then email.
            if (!empty($row['gstin'])) {
                $supplier = Supplier::where('gstin', $row['gstin'])->first();
            }

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


/*
|--------------------------------------------------------------------------
| Products Sheet
|--------------------------------------------------------------------------
*/

class PurchaseProductsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            if (empty($row['name'])) {
                continue;
            }

            /*
             * Your current Product export does NOT have a SKU/product_code,
             * so for this first migration test we match by product name.
             */
            $product = Product::where('name', trim($row['name']))->first();

            if ($product) {
                $product->update([
                    'hsn_code'      => $row['hsn_code'] ?? null,
                    'gst_rate'      => $row['gst_rate'] ?? 0,
                    'mrp'           => $row['mrp'] ?? 0,
                    'rate'          => $row['rate'] ?? 0,
                    'reorder_level' => $row['reorder_level'] ?? 0,
                    'description'   => $row['description'] ?? null,

                    // IMPORTANT:
                    // Do NOT update current_stock here.
                    // Stock will be generated from Purchase Items.
                ]);

                continue;
            }

            Product::create([
                'name'          => trim($row['name']),
                'hsn_code'      => $row['hsn_code'] ?? null,
                'gst_rate'      => $row['gst_rate'] ?? 0,
                'mrp'           => $row['mrp'] ?? 0,
                'rate'          => $row['rate'] ?? 0,
                'reorder_level' => $row['reorder_level'] ?? 0,
                'description'   => $row['description'] ?? null,

                // Fresh product starts from zero.
                // Purchase Items will add stock.
                'current_stock' => 0,
            ]);
        }
    }
}


/*
|--------------------------------------------------------------------------
| Purchases Sheet
|--------------------------------------------------------------------------
*/

class PurchaseDetailsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection_(Collection $rows)
    {
        foreach ($rows as $row) {

            if (
                empty($row['purchase_number']) ||
                empty($row['supplier'])
            ) {
                continue;
            }

            $supplier = Supplier::where(
                'name',
                trim($row['supplier'])
            )->first();

            if (!$supplier) {
                throw new \Exception(
                    "Supplier not found: {$row['supplier']}"
                );
            }

            Purchase::create([
                'purchase_number' => $row['purchase_number'],
                //'date'            => $row['date'],
                'date' => is_numeric($row['date'])
                        ? ExcelDate::excelToDateTimeObject($row['date'])->format('Y-m-d')
                        : date('Y-m-d', strtotime($row['date'])),
                'supplier_id'     => $supplier->id,

                // Calculated later from Purchase Items.
                'total_amount'    => 0,
                'gst_amount'      => 0,
                'grand_total'     => 0,

                'status'          => $row['status'] ?? 'Unpaid',
            ]);
        }
    }
    public function collection(Collection $rows)
{
    foreach ($rows as $row) {

        if (
            empty($row['purchase_number']) ||
            empty($row['supplier'])
        ) {
            continue;
        }

        $purchaseNumber = trim($row['purchase_number']);

        // CHECK BEFORE INSERT
        if (Purchase::where('purchase_number', $purchaseNumber)->exists()) {
            throw new \Exception(
                "Purchase No. {$purchaseNumber} already exists."
            );
        }

        $supplier = Supplier::where(
            'name',
            trim($row['supplier'])
        )->first();

        if (!$supplier) {
            throw new \Exception(
                "Supplier {$row['supplier']} not found."
            );
        }

        Purchase::create([
            'purchase_number' => $purchaseNumber,

            'date' => is_numeric($row['date'])
                ? ExcelDate::excelToDateTimeObject($row['date'])->format('Y-m-d')
                : date('Y-m-d', strtotime($row['date'])),

            'supplier_id'  => $supplier->id,
            'total_amount' => 0,
            'gst_amount'   => 0,
            'grand_total'  => 0,
            'status'       => $row['status'] ?? 'Unpaid',
        ]);
    }
}
}


/*
|--------------------------------------------------------------------------
| Purchase Items Sheet
|--------------------------------------------------------------------------
*/

class PurchaseItemsSheetImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            $purchaseNumbers = [];

            foreach ($rows as $row) {

                if (
                    empty($row['purchase_number']) ||
                    empty($row['product']) ||
                    empty($row['quantity'])
                ) {
                    continue;
                }

                $purchase = Purchase::where(
                    'purchase_number',
                    $row['purchase_number']
                )->first();

                if (!$purchase) {
                    throw new \Exception(
                        "Purchase not found: {$row['purchase_number']}"
                    );
                }

                $product = Product::where(
                    'name',
                    trim($row['product'])
                )->first();

                if (!$product) {
                    throw new \Exception(
                        "Product not found: {$row['product']}"
                    );
                }

                $quantity = (int) $row['quantity'];
                $rate     = (float) ($row['rate'] ?? 0);
                $gstRate  = (float) ($row['gst_rate'] ?? 0);

                /*
                 * For the first migration version we're using the
                 * calculated values rather than trusting Excel totals.
                 */
                $subtotal = $quantity * $rate;
                $taxAmount = $subtotal * ($gstRate / 100);
                $total = $subtotal + $taxAmount;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $product->id,
                    'quantity'    => $quantity,
                    'rate'        => $rate,
                    'gst_rate'    => $gstRate,
                    'subtotal'    => $subtotal,
                    'tax_amount'  => $taxAmount,
                    'total'       => $total,
                ]);

                /*
                 * Existing StockMovement logic:
                 * +quantity increases Product.current_stock
                 * and records the stock movement.
                 */
                StockMovement::record(
                    $product,
                    'purchase',
                    $quantity,
                    'purchase',
                    $purchase->id,
                    'Imported purchase ' . $purchase->purchase_number
                );

                $purchaseNumbers[] = $purchase->purchase_number;
            }

            /*
             * Recalculate Purchase totals from imported items.
             */
            foreach (array_unique($purchaseNumbers) as $purchaseNumber) {

                $purchase = Purchase::where(
                    'purchase_number',
                    $purchaseNumber
                )->first();

                $subtotal = $purchase->items()->sum('subtotal');
                $gst      = $purchase->items()->sum('tax_amount');
                $total    = $purchase->items()->sum('total');

                $purchase->update([
                    'total_amount' => $subtotal,
                    'gst_amount'   => $gst,
                    'grand_total'  => $total,
                ]);
            }
        });
    }
}