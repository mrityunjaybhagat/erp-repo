<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'CloudERP API',
        'version' => 'v1',
    ]);
});

// Add these to routes/api.php.
use App\Http\Controllers\Api\AuthModule\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\CashVoucherController;
use App\Http\Controllers\Api\UserTypeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
// use App\Http\Controllers\Api\PostController;
// use App\Http\Controllers\Api\MagazineController;
use App\Http\Controllers\Api\SupplierPaymentController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\ReportController;

use App\Http\Controllers\Api\ContactEnquiryController;
use App\Http\Controllers\Api\FaqController;
// use App\Http\Controllers\Api\ShippingModule\PriceEnquiryController;
// use App\Http\Controllers\Api\ShippingModule\ShipmentController;


Route::apiResource('customers', CustomerController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('purchases', PurchaseController::class);
Route::apiResource('invoices', InvoiceController::class);
Route::apiResource('expenses', ExpenseController::class);
Route::apiResource('cash-vouchers', CashVoucherController::class);
Route::apiResource('user-types', UserTypeController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('categories', CategoryController::class);
// Route::apiResource('posts', PostController::class);
// Route::apiResource('magazines', MagazineController::class);

//Auth COntroler
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('logout', [AuthController::class, 'logout']);


Route::get('reports/gst-summary', [ReportController::class, 'gstSummary']);
Route::get('reports/balances-summary', [ReportController::class, 'balancesSummary']);
Route::get('reports/profit-and-loss', [ReportController::class, 'profitAndLoss']);
Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);

Route::get('stock', [StockController::class, 'index']);
Route::get('stock/{product}/movements', [StockController::class, 'movements']);
Route::post('stock/adjustments', [StockController::class, 'storeAdjustment']);

Route::get('invoices/{invoice}/payments', [PaymentController::class, 'forInvoice']);
Route::get('payments', [PaymentController::class, 'index']);
Route::post('payments', [PaymentController::class, 'store']);
Route::delete('payments/{payment}', [PaymentController::class, 'destroy']);

// Supplier Payments
Route::get('supplier-payments', [SupplierPaymentController::class, 'index']);
Route::post('supplier-payments', [SupplierPaymentController::class, 'store']);
Route::delete('supplier-payments/{supplierPayment}', [SupplierPaymentController::class, 'destroy']);

// Ledgers
Route::get('ledgers/customers/{customer}', [LedgerController::class, 'customer']);
Route::get('ledgers/suppliers/{supplier}', [LedgerController::class, 'supplier']);
Route::get('ledgers/cash', [LedgerController::class, 'cash']);
Route::get('ledgers/bank', [LedgerController::class, 'bank']);
Route::get('ledgers/company', [LedgerController::class, 'company']);

// Reports
Route::get('reports/gst-summary', [ReportController::class, 'gstSummary']);
Route::get('reports/balances-summary', [ReportController::class, 'balancesSummary']);
Route::get('reports/profit-and-loss', [ReportController::class, 'profitAndLoss']);
Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
 

// Sales module NOT included — still waiting on confirmation of how it
// relates to Invoices (same thing renamed, or a pre-invoice stage?).

// Route::get('/track/{awb}', [ShipmentController::class, 'track']);
// Route::get('/faqs', [FaqController::class, 'index']);
// Route::post('/price-enquiries', [PriceEnquiryController::class, 'store']);
// Route::post('/contact', [ContactEnquiryController::class, 'store']);

// //Shipment
// Route::get('/shipments', [ShipmentController::class, 'index']);
// Route::post('/shipments', [ShipmentController::class, 'store']);
// Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);

