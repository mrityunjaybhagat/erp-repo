<?php

namespace Database\Seeders;

use App\Models\CashVoucher;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Magazine;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Run with: php artisan migrate:fresh --seed
// (migrate:fresh drops and recreates all tables first, so this is safe
// to re-run as many times as you want while testing endpoints.)
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- User types + users ----
        $admin = UserType::create(['name' => 'Admin', 'description' => 'Full access']);
        $accountant = UserType::create(['name' => 'Accountant', 'description' => 'Billing, purchases, payments']);
        $sales = UserType::create(['name' => 'Sales', 'description' => 'Customers, invoices']);

        User::create(['name' => 'Riya Sharma', 'email' => 'riya@clouderp.test', 'password' => Hash::make('password'), 'user_type_id' => $admin->id]);
        User::create(['name' => 'Manoj Verma', 'email' => 'manoj@clouderp.test', 'password' => Hash::make('password'), 'user_type_id' => $accountant->id]);
        User::create(['name' => 'Priya Nair', 'email' => 'priya@clouderp.test', 'password' => Hash::make('password'), 'user_type_id' => $sales->id]);

        // ---- Customers ----
        $customers = collect([
            ['name' => 'Agrasen Medical Store', 'email' => 'agrasen.medical@example.com', 'phone' => '07712533535', 'address' => 'Samta Colony, Raipur', 'gstin' => '22AEFPA4160G1Z7'],
            ['name' => 'Shri Sai Surgical', 'email' => 'shrisai@example.com', 'phone' => '9893972000', 'address' => 'Railway Station Road, Raipur', 'gstin' => '22AGQPR5431E2ZE'],
            ['name' => 'Borneo Pharmacy', 'email' => 'borneo@example.com', 'phone' => '9329141528', 'address' => 'Pachpedi Naka, Raipur', 'gstin' => '22AAFCJ2307K1ZV'],
            ['name' => 'Manoj Bhaiya', 'email' => 'na@example.com', 'phone' => '6267793834', 'address' => 'Raipur', 'gstin' => null],
        ])->map(fn ($c) => Customer::create($c));

        // ---- Suppliers ----
        $suppliers = collect([
            ['name' => 'Unicharm Distributors', 'email' => 'orders@unicharm-dist.example', 'phone' => '9800011122', 'address' => 'Industrial Area, Raipur', 'gstin' => '22AABCU1234F1Z5'],
            ['name' => 'Kolkata Paper Products', 'email' => 'sales@kolkatapaper.example', 'phone' => '9800033344', 'address' => 'Howrah, West Bengal', 'gstin' => '19AABCK5678F1Z9'],
        ])->map(fn ($s) => Supplier::create($s));

        // ---- Products ----
        $products = collect([
            ['name' => 'MamyPoko Pants Standard S1', 'hsn_code' => '96190030', 'gst_rate' => 5.00, 'mrp' => 10.00, 'rate' => 7.24, 'reorder_level' => 480, 'description' => null],
            ['name' => 'MamyPoko Pants Standard M4', 'hsn_code' => '96190030', 'gst_rate' => 5.00, 'mrp' => 48.00, 'rate' => 34.74, 'reorder_level' => 240, 'description' => null],
            ['name' => 'Sofy Antibacteria XL 7P', 'hsn_code' => '96190010', 'gst_rate' => 0.00, 'mrp' => 70.00, 'rate' => 51.16, 'reorder_level' => 96, 'description' => null],
            ['name' => 'Mamy Poko Wipes Komal Care', 'hsn_code' => '33071090', 'gst_rate' => 18.00, 'mrp' => 99.00, 'rate' => 79.20, 'reorder_level' => 24, 'description' => null],
            ['name' => 'SOFY BODYFIT NIGHTS XXL 6P', 'hsn_code' => '96190010', 'gst_rate' => 0.00, 'mrp' => 50.00, 'rate' => 36.52, 'reorder_level' => 48, 'description' => null],
        ])->map(fn ($p) => Product::create($p));

        // ---- Categories (blog) ----
        $categories = collect([
            ['name' => 'Heritage', 'slug' => 'heritage', 'description' => 'Landmarks and the buildings that outlived their builders.'],
            ['name' => 'Cityscape', 'slug' => 'cityscape', 'description' => 'The city as it looks right now.'],
            ['name' => 'Culture', 'slug' => 'culture', 'description' => 'Traditions, festivals and the people who keep them alive.'],
        ])->map(fn ($c) => Category::create($c));

        // ---- Posts ----
        Post::create([
            'title' => "Calcutta's Edifice: The Bones of a City",
            'slug' => 'calcuttas-edifice-' . Str::random(5),
            'excerpt' => 'A walk through the buildings that shaped the city.',
            'content' => '<p>Sample post content goes here.</p>',
            'category_id' => $categories[0]->id,
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);
        Post::create([
            'title' => 'Changing Face of the Maidan',
            'slug' => 'changing-face-of-the-maidan-' . Str::random(5),
            'excerpt' => 'How the city\'s green heart has shifted over decades.',
            'content' => '<p>Sample post content goes here.</p>',
            'category_id' => $categories[1]->id,
            'status' => 'draft',
        ]);

        // ---- Magazines ----
        Magazine::create(['title' => 'August Issue 2026', 'slug' => 'august-issue-2026-' . Str::random(5), 'intro' => 'Featuring the city\'s monsoon stories.', 'issue_month' => 'August', 'issue_year' => '2026', 'status' => 'published', 'published_at' => now()->subDays(10)]);
        Magazine::create(['title' => 'July Issue 2026', 'slug' => 'july-issue-2026-' . Str::random(5), 'intro' => 'A grand old edifice, and the city\'s love of monsoon fest.', 'issue_month' => 'July', 'issue_year' => '2026', 'status' => 'published', 'published_at' => now()->subDays(40)]);

        // ---- Purchases (stock IN) ----
        $purchase = Purchase::create(['purchase_number' => 'P000001', 'date' => now()->subDays(15)->toDateString(), 'supplier_id' => $suppliers[0]->id, 'status' => 'Paid']);
        $pTotal = 0; $pGst = 0;
        foreach ([[0, 200, 7.24, 5.00], [1, 100, 34.74, 5.00]] as [$idx, $qty, $rate, $gstRate]) {
            $product = $products[$idx];
            $subtotal = $qty * $rate;
            $tax = round($subtotal * $gstRate / 100, 2);
            PurchaseItem::create(['purchase_id' => $purchase->id, 'product_id' => $product->id, 'quantity' => $qty, 'rate' => $rate, 'gst_rate' => $gstRate, 'subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal]);
            StockMovement::record($product, 'purchase', $qty, 'Purchase', $purchase->id);
            $pTotal += $subtotal; $pGst += $tax;
        }
        $purchase->update(['total_amount' => $pTotal, 'gst_amount' => $pGst, 'grand_total' => $pTotal + $pGst]);

        // ---- Invoices (stock OUT) ----
        $invoice = Invoice::create(['invoice_number' => 'A000001', 'date' => now()->subDays(5)->toDateString(), 'customer_id' => $customers[0]->id, 'status' => 'Unpaid']);
        $iTotal = 0; $iGst = 0;
        foreach ([[0, 24, 10.00, 30, 6.67, 5.00], [3, 10, 99.00, 50, 41.95, 18.00]] as [$idx, $qty, $mrp, $discount, $rate, $gstRate]) {
            $product = $products[$idx];
            $subtotal = $qty * $rate;
            $tax = round($subtotal * $gstRate / 100, 2);
            InvoiceItem::create(['invoice_id' => $invoice->id, 'product_id' => $product->id, 'mrp' => $mrp, 'rate' => $rate, 'discount' => $discount, 'quantity' => $qty, 'gst_rate' => $gstRate, 'subtotal' => $subtotal, 'tax_amount' => $tax, 'total' => $subtotal]);
            StockMovement::record($product, 'sale', -$qty, 'Invoice', $invoice->id);
            $iTotal += $subtotal; $iGst += $tax;
        }
        $invoice->update(['total_amount' => $iTotal, 'gst_amount' => $iGst, 'grand_total' => $iTotal + $iGst]);

        // A second invoice, left fully unpaid, no payment against it — so
        // pagination/filtering by status has something to actually filter.
        $invoice2 = Invoice::create(['invoice_number' => 'A000002', 'date' => now()->subDays(2)->toDateString(), 'customer_id' => $customers[1]->id, 'status' => 'Unpaid']);
        $product = $products[4];
        $qty = 6; $rate = 36.52; $gstRate = 0.00;
        $subtotal = $qty * $rate;
        InvoiceItem::create(['invoice_id' => $invoice2->id, 'product_id' => $product->id, 'mrp' => 50.00, 'rate' => $rate, 'discount' => 27, 'quantity' => $qty, 'gst_rate' => $gstRate, 'subtotal' => $subtotal, 'tax_amount' => 0, 'total' => $subtotal]);
        StockMovement::record($product, 'sale', -$qty, 'Invoice', $invoice2->id);
        $invoice2->update(['total_amount' => $subtotal, 'gst_amount' => 0, 'grand_total' => $subtotal]);

        // ---- Payment against the first invoice ----
        Payment::create(['invoice_id' => $invoice->id, 'amount' => $invoice->grand_total, 'method' => 'UPI', 'date' => now()->subDays(4)->toDateString(), 'reference_note' => 'UPI-REF-88291']);
        $invoice->update(['status' => 'Paid']);

        // ---- Expenses ----
        Expense::create(['category' => 'Utilities', 'vendor_name' => 'State Electricity Board', 'description' => 'Monthly electricity bill', 'amount' => 4200.00, 'payment_mode' => 'Bank Transfer', 'date' => now()->subDays(6)->toDateString()]);
        Expense::create(['category' => 'Furniture', 'vendor_name' => 'Raipur Furniture Mart', 'description' => 'Office chairs', 'amount' => 12500.00, 'payment_mode' => 'Cheque', 'date' => now()->subDays(20)->toDateString()]);
        Expense::create(['category' => 'Rent', 'vendor_name' => 'Property Owner', 'description' => 'Warehouse rent, August', 'amount' => 18000.00, 'payment_mode' => 'Bank Transfer', 'date' => now()->subDays(1)->toDateString()]);

        // ---- Cash vouchers ----
        CashVoucher::create(['voucher_number' => 'CV000001', 'type' => 'Payment', 'party_name' => 'Petty cash - office supplies', 'amount' => 850.00, 'narration' => 'Stationery for office', 'payment_mode' => 'Cash', 'date' => now()->subDays(3)->toDateString()]);
        CashVoucher::create(['voucher_number' => 'CV000002', 'type' => 'Receipt', 'party_name' => 'Walk-in cash sale', 'amount' => 1250.00, 'narration' => 'Cash sale, no formal invoice', 'payment_mode' => 'Cash', 'date' => now()->subDays(1)->toDateString()]);
    }
}
