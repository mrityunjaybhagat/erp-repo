<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\CashVoucher;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /**
     * GET /ledgers/customers/{customer}
     * Invoices (debit — increases what they owe) + Payments received
     * (credit — reduces it), chronological, with a running balance.
     * (Stock Ledger already exists: GET /stock/{product}/movements.)
     */
    public function customer(Customer $customer)
    {
        $entries = collect();

        foreach ($customer->invoices()->get() as $inv) {
            $entries->push(['date' => $inv->date, 'type' => 'Invoice', 'reference' => $inv->invoice_number, 'debit' => (float) $inv->grand_total, 'credit' => 0]);
        }
        foreach (Payment::whereHas('invoice', fn ($q) => $q->where('customer_id', $customer->id))->with('invoice')->get() as $pay) {
            $entries->push(['date' => $pay->date, 'type' => 'Payment Received', 'reference' => $pay->invoice->invoice_number ?? null, 'debit' => 0, 'credit' => (float) $pay->amount]);
        }

        return response()->json(['data' => $this->withRunningBalance($entries, 'debit'), 'customer' => $customer]);
    }

    /**
     * GET /ledgers/suppliers/{supplier}
     * Purchases (credit — increases what you owe them) + Payments made
     * (debit — reduces it).
     */
    public function supplier(Supplier $supplier)
    {
        $entries = collect();

        foreach ($supplier->purchases()->get() as $pur) {
            $entries->push(['date' => $pur->date, 'type' => 'Purchase', 'reference' => $pur->purchase_number, 'debit' => 0, 'credit' => (float) $pur->grand_total]);
        }
        foreach (SupplierPayment::whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->id))->with('purchase')->get() as $pay) {
            $entries->push(['date' => $pay->date, 'type' => 'Payment Made', 'reference' => $pay->purchase->purchase_number ?? null, 'debit' => (float) $pay->amount, 'credit' => 0]);
        }

        return response()->json(['data' => $this->withRunningBalance($entries), 'supplier' => $supplier]);
    }

    /**
     * GET /ledgers/company?page=&limit=&search=&from=&to=&type=
     * Company-wide activity feed — Invoices, Purchases, Customer
     * Payments, Supplier Payments, Expenses, Cash Vouchers, merged and
     * sorted by date. This is NOT a formal double-entry General Ledger
     * and deliberately has no running balance: Invoices/Purchases
     * represent receivables/payables, not actual cash movement, so a
     * single balance across this mixed feed wouldn't mean anything
     * real. Use Cash/Bank Ledger for an actual cash balance.
     *
     * Pagination note: this merges six different tables in PHP rather
     * than one SQL query (a UNION across six differently-shaped tables
     * would be its own maintenance burden for an activity feed like
     * this). Fine at normal ERP data volumes; if any one of these
     * tables grows into the hundreds of thousands of rows, this should
     * move to a real UNION query or a materialized activity table.
     */
    public function company(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $search = $request->query('search');
        $type = $request->query('type');

        $entries = collect();
        $dateFilter = fn ($q) => $q
            ->when($from, fn ($q2) => $q2->whereDate('date', '>=', $from))
            ->when($to, fn ($q2) => $q2->whereDate('date', '<=', $to));

        foreach ($dateFilter(Invoice::with('customer'))->get() as $inv) {
            $entries->push([
                'date' => $inv->date, 'type' => 'Invoice', 'reference' => $inv->invoice_number,
                'party' => $inv->customer->name ?? null, 'debit' => 0, 'credit' => (float) $inv->grand_total,
                'payment_mode' => null, 'notes' => null,
            ]);
        }

        foreach ($dateFilter(Purchase::with('supplier'))->get() as $pur) {
            $entries->push([
                'date' => $pur->date, 'type' => 'Purchase', 'reference' => $pur->purchase_number,
                'party' => $pur->supplier->name ?? null, 'debit' => (float) $pur->grand_total, 'credit' => 0,
                'payment_mode' => null, 'notes' => null,
            ]);
        }

        foreach ($dateFilter(Payment::with('invoice.customer'))->get() as $pay) {
            $entries->push([
                'date' => $pay->date, 'type' => 'Payment Received', 'reference' => $pay->invoice->invoice_number ?? null,
                'party' => $pay->invoice->customer->name ?? null, 'debit' => 0, 'credit' => (float) $pay->amount,
                'payment_mode' => $pay->method, 'notes' => $pay->reference_note,
            ]);
        }

        foreach ($dateFilter(SupplierPayment::with('purchase.supplier'))->get() as $pay) {
            $entries->push([
                'date' => $pay->date, 'type' => 'Payment Made', 'reference' => $pay->purchase->purchase_number ?? null,
                'party' => $pay->purchase->supplier->name ?? null, 'debit' => (float) $pay->amount, 'credit' => 0,
                'payment_mode' => $pay->method, 'notes' => $pay->reference_note,
            ]);
        }

        foreach ($dateFilter(Expense::query())->get() as $e) {
            $entries->push([
                'date' => $e->date, 'type' => 'Expense', 'reference' => $e->category,
                'party' => $e->vendor_name, 'debit' => (float) $e->amount, 'credit' => 0,
                'payment_mode' => $e->payment_mode, 'notes' => $e->description,
            ]);
        }

        foreach ($dateFilter(CashVoucher::query())->get() as $v) {
            $isReceipt = $v->type === 'Receipt';
            $entries->push([
                'date' => $v->date, 'type' => 'Cash Voucher', 'reference' => $v->voucher_number,
                'party' => $v->party_name, 'debit' => $isReceipt ? 0 : (float) $v->amount, 'credit' => $isReceipt ? (float) $v->amount : 0,
                'payment_mode' => $v->payment_mode, 'notes' => ($isReceipt ? 'Receipt' : 'Payment') . ($v->narration ? ": {$v->narration}" : ''),
            ]);
        }

        if ($type) {
            $entries = $entries->filter(fn ($e) => $e['type'] === $type);
        }
        if ($search) {
            $needle = strtolower($search);
            $entries = $entries->filter(fn ($e) =>
                str_contains(strtolower($e['reference'] ?? ''), $needle) ||
                str_contains(strtolower($e['party'] ?? ''), $needle)
            );
        }

        $sorted = $entries->sortByDesc('date')->values();
        $totalRecords = $sorted->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        return response()->json([
            'note' => 'Company Activity Ledger — a chronological transaction feed for browsing and audit, not a formal double-entry General Ledger. No running balance is shown, since Invoices/Purchases are receivables/payables, not cash movement.',
            'data' => $sorted->slice($offset, $limit)->values(),
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * GET /ledgers/cash?from=&to=
     * GET /ledgers/bank?from=&to=
     * Both built the same way — split by payment_mode across every
     * cash-affecting table. "Cash" mode = Cash Ledger; everything else
     * (Bank Transfer/UPI/Cheque/Other) = Bank Ledger.
     */
    public function cash(Request $request)
    {
        return $this->cashOrBank($request, true);
    }

    public function bank(Request $request)
    {
        return $this->cashOrBank($request, false);
    }

    private function cashOrBank(Request $request, bool $isCash)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $entries = collect();

        $payments = Payment::with('invoice');
        $isCash ? $payments->where('method', 'Cash') : $payments->where('method', '!=', 'Cash');
        if ($from) $payments->whereDate('date', '>=', $from);
        if ($to) $payments->whereDate('date', '<=', $to);
        foreach ($payments->get() as $p) {
            $entries->push(['date' => $p->date, 'type' => 'Payment Received', 'reference' => $p->invoice->invoice_number ?? null, 'debit' => 0, 'credit' => (float) $p->amount]);
        }

        $supplierPayments = SupplierPayment::with('purchase');
        $isCash ? $supplierPayments->where('method', 'Cash') : $supplierPayments->where('method', '!=', 'Cash');
        if ($from) $supplierPayments->whereDate('date', '>=', $from);
        if ($to) $supplierPayments->whereDate('date', '<=', $to);
        foreach ($supplierPayments->get() as $p) {
            $entries->push(['date' => $p->date, 'type' => 'Payment Made', 'reference' => $p->purchase->purchase_number ?? null, 'debit' => (float) $p->amount, 'credit' => 0]);
        }

        $expenses = Expense::query();
        $isCash ? $expenses->where('payment_mode', 'Cash') : $expenses->where('payment_mode', '!=', 'Cash');
        if ($from) $expenses->whereDate('date', '>=', $from);
        if ($to) $expenses->whereDate('date', '<=', $to);
        foreach ($expenses->get() as $e) {
            $entries->push(['date' => $e->date, 'type' => 'Expense', 'reference' => $e->category, 'debit' => (float) $e->amount, 'credit' => 0]);
        }

        $vouchers = CashVoucher::query();
        $isCash ? $vouchers->where('payment_mode', 'Cash') : $vouchers->where('payment_mode', '!=', 'Cash');
        if ($from) $vouchers->whereDate('date', '>=', $from);
        if ($to) $vouchers->whereDate('date', '<=', $to);
        foreach ($vouchers->get() as $v) {
            $isReceipt = $v->type === 'Receipt';
            $entries->push(['date' => $v->date, 'type' => "Cash Voucher ({$v->type})", 'reference' => $v->voucher_number, 'debit' => $isReceipt ? 0 : (float) $v->amount, 'credit' => $isReceipt ? (float) $v->amount : 0]);
        }

        return response()->json(['data' => $this->withRunningBalance($entries)]);
    }

    private function withRunningBalance($entries, string $increaseOn = 'credit'): array
    {
        $sorted = $entries->sortBy('date')->values();
        $balance = 0;
        return $sorted->map(function ($e) use (&$balance, $increaseOn) {
            $balance += $increaseOn === 'debit' ? ($e['debit'] - $e['credit']) : ($e['credit'] - $e['debit']);
            $e['balance'] = round($balance, 2);
            return $e;
        })->all();
    }
}