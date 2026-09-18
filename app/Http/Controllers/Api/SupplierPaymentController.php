<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    // GET /supplier-payments?page=&limit=&search=&from=&to=&purchase_id=
    public function index(Request $request)
    {
        $query = SupplierPayment::with('purchase.supplier');

        if ($purchaseId = $request->query('purchase_id')) {
            $query->where('purchase_id', $purchaseId);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('purchase', fn ($q) => $q->where('purchase_number', 'like', "%{$search}%"));
        }
        if ($from = $request->query('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('date', '<=', $to);
        }

        $totalRecords = $query->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $data = $query->orderByDesc('date')->skip($offset)->take($limit)->get();

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    // POST /supplier-payments — this is PAYMENT MADE (to a supplier,
    // against a Purchase). Recalculates the purchase's status the same
    // way PaymentController does for invoices.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:Cash,Bank Transfer,UPI,Cheque,Other',
            'date' => 'required|date',
            'reference_note' => 'nullable|string|max:255',
        ]);

        $payment = SupplierPayment::create($validated);

        $purchase = Purchase::findOrFail($validated['purchase_id']);
        $totalPaid = $purchase->payments()->sum('amount');
        $purchase->status = $totalPaid >= $purchase->grand_total ? 'Paid' : 'Unpaid';
        $purchase->save();

        return response()->json($payment, 201);
    }

    public function destroy(SupplierPayment $supplierPayment)
    {
        $purchase = $supplierPayment->purchase;
        $supplierPayment->delete();

        if ($purchase) {
            $totalPaid = $purchase->payments()->sum('amount');
            $purchase->status = $totalPaid >= $purchase->grand_total ? 'Paid' : 'Unpaid';
            $purchase->save();
        }

        return response()->json(null, 204);
    }
}
