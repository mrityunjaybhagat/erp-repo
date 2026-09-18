<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // GET /payments?page=&limit=&search=&from=&to=&invoice_id=
    public function index(Request $request)
    {
        $query = Payment::with('invoice');

        if ($invoiceId = $request->query('invoice_id')) {
            $query->where('invoice_id', $invoiceId);
        }
        if ($search = $request->query('search')) {
            $query->whereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"));
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

    // GET /invoices/{invoice}/payments?page=&limit=&from=&to=
    public function forInvoice(Request $request, Invoice $invoice)
    {
        $query = $invoice->payments();

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

    /**
     * POST /payments — this is PAYMENT RECEIVED (from a customer,
     * against an Invoice). Recalculates the invoice's status from
     * SUM(payments) vs grand_total.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:Cash,Bank Transfer,UPI,Cheque,Other',
            'date' => 'required|date',
            'reference_note' => 'nullable|string|max:255',
        ]);

        $payment = Payment::create($validated);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $totalPaid = $invoice->payments()->sum('amount');
        $invoice->status = $totalPaid >= $invoice->grand_total ? 'Paid' : 'Unpaid';
        $invoice->save();

        return response()->json($payment, 201);
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;
        $payment->delete();

        if ($invoice) {
            $totalPaid = $invoice->payments()->sum('amount');
            $invoice->status = $totalPaid >= $invoice->grand_total ? 'Paid' : 'Unpaid';
            $invoice->save();
        }

        return response()->json(null, 204);
    }
}
