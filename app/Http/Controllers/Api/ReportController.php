<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;

/**
 * IMPORTANT — read before wiring these into anything customer-facing
 * or tax-filing-facing:
 *
 * gstSummary() is a real, correct aggregation of your actual invoice
 * and purchase line items — safe to rely on.
 *
 * balancesSummary(), profitAndLoss(), and balanceSheet() are NOT formal
 * double-entry accounting statements. There's no Chart of Accounts or
 * Journal Entry system behind them — they're derived directly from
 * Invoices/Purchases/Payments/Expenses/Stock. Two specific limitations:
 *   1. products.rate is used as both cost price (Purchases) and sale
 *      price (Invoices) in this schema — so "profit" here is Invoice
 *      revenue minus Purchase-cost-at-current-rate, not a true
 *      per-transaction COGS calculation.
 *   2. balanceSheet()'s "equity" is a plug figure (assets − liabilities),
 *      not tracked independently — a real Balance Sheet tracks capital
 *      and retained earnings over time.
 * Treat these three as management summaries, not audited financials.
 */
class ReportController extends Controller
{
    // GET /reports/gst-summary?from=&to=
    // Real, correct: output GST (from Invoices, i.e. GST you collected)
    // vs input GST (from Purchases, i.e. GST you paid — your Input Tax
    // Credit), both grouped by rate.
    public function gstSummary(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $invoiceItems = InvoiceItem::whereHas('invoice', function ($q) use ($from, $to) {
            if ($from) $q->whereDate('date', '>=', $from);
            if ($to) $q->whereDate('date', '<=', $to);
        })->get();

        $purchaseItems = PurchaseItem::whereHas('purchase', function ($q) use ($from, $to) {
            if ($from) $q->whereDate('date', '>=', $from);
            if ($to) $q->whereDate('date', '<=', $to);
        })->get();

        $groupByRate = function ($items) {
            $out = [];
            foreach ($items as $item) {
                $rate = $item->gst_rate;
                $out[$rate] ??= ['taxable_value' => 0, 'tax_amount' => 0];
                $out[$rate]['taxable_value'] += (float) $item->subtotal;
                $out[$rate]['tax_amount'] += (float) $item->tax_amount;
            }
            return $out;
        };

        return response()->json([
            'output_gst' => $groupByRate($invoiceItems), // GST collected from customers
            'input_gst' => $groupByRate($purchaseItems),  // GST paid to suppliers (your ITC)
            'net_gst_payable' => round(
                collect($groupByRate($invoiceItems))->sum('tax_amount') - collect($groupByRate($purchaseItems))->sum('tax_amount'),
                2
            ),
        ]);
    }

    // GET /reports/balances-summary?as_of=YYYY-MM-DD
    // "Trial-Balance-like" snapshot — see class-level caveat. Without
    // as_of, this is "right now" (uses live current_stock and every
    // payment/expense ever recorded). With as_of, receivables/payables
    // only count invoices/purchases dated on or before it, and stock
    // value is reconstructed from the stock_movements ledger up to
    // that date instead of the live current_stock cache — the ledger
    // is the only way to get a historically-accurate quantity.
    public function balancesSummary(Request $request)
    {
        $asOf = $request->query('as_of');

        $receivablesQuery = Invoice::where('status', '!=', 'Paid');
        if ($asOf) $receivablesQuery->whereDate('date', '<=', $asOf);
        $receivables = $receivablesQuery->get()->sum(function ($inv) use ($asOf) {
            $paid = $inv->payments()->when($asOf, fn ($q) => $q->whereDate('date', '<=', $asOf))->sum('amount');
            return $inv->grand_total - $paid;
        });

        $payablesQuery = Purchase::where('status', '!=', 'Paid');
        if ($asOf) $payablesQuery->whereDate('date', '<=', $asOf);
        $payables = $payablesQuery->get()->sum(function ($pur) use ($asOf) {
            $paid = $pur->payments()->when($asOf, fn ($q) => $q->whereDate('date', '<=', $asOf))->sum('amount');
            return $pur->grand_total - $paid;
        });

        if ($asOf) {
            // Historical quantity per product = sum of stock_movements up to that date.
            $stockValue = Product::all()->sum(function ($p) use ($asOf) {
                $qtyAsOf = $p->stockMovements()->whereDate('date', '<=', $asOf)->sum('quantity_change');
                return $qtyAsOf * $p->rate;
            });
        } else {
            $stockValue = Product::all()->sum(fn ($p) => $p->current_stock * $p->rate);
        }

        $ledgerParams = $asOf ? ['to' => $asOf] : [];
        $cashRequest = Request::create('', 'GET', $ledgerParams);
        $bankRequest = Request::create('', 'GET', $ledgerParams);
        $cash = app(LedgerController::class)->cash($cashRequest)->getData(true)['data'];
        $bank = app(LedgerController::class)->bank($bankRequest)->getData(true)['data'];
        $cashBalance = end($cash)['balance'] ?? 0;
        $bankBalance = end($bank)['balance'] ?? 0;

        return response()->json([
            'note' => 'Derived summary, not a formal double-entry Trial Balance — see API documentation.',
            'as_of' => $asOf ?: 'current',
            'receivables' => round($receivables, 2),
            'payables' => round($payables, 2),
            'stock_value' => round($stockValue, 2),
            'cash_balance' => $cashBalance,
            'bank_balance' => $bankBalance,
        ]);
    }

    // GET /reports/profit-and-loss?from=&to=
    public function profitAndLoss(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $invoiceItems = InvoiceItem::whereHas('invoice', function ($q) use ($from, $to) {
            if ($from) $q->whereDate('date', '>=', $from);
            if ($to) $q->whereDate('date', '<=', $to);
        })->with('product')->get();

        $revenue = $invoiceItems->sum('subtotal');
        // Approximate COGS: quantity sold * that product's CURRENT rate
        // (see class-level caveat — rate isn't a historical cost record).
        $cogs = $invoiceItems->sum(fn ($item) => $item->quantity * ($item->product->rate ?? 0));

        $expenseQuery = Expense::query();
        if ($from) $expenseQuery->whereDate('date', '>=', $from);
        if ($to) $expenseQuery->whereDate('date', '<=', $to);
        $expenses = $expenseQuery->sum('amount');

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return response()->json([
            'note' => 'Derived summary, not a formal P&L — see API documentation (COGS is approximated from current product rate).',
            'revenue' => round($revenue, 2),
            'cogs_approx' => round($cogs, 2),
            'gross_profit_approx' => round($grossProfit, 2),
            'operating_expenses' => round($expenses, 2),
            'net_profit_approx' => round($netProfit, 2),
        ]);
    }

    // GET /reports/balance-sheet?as_of=YYYY-MM-DD
    public function balanceSheet(Request $request)
    {
        $balances = $this->balancesSummary($request)->getData(true);

        $assets = $balances['receivables'] + $balances['stock_value'] + $balances['cash_balance'] + $balances['bank_balance'];
        $liabilities = $balances['payables'];
        $equity = $assets - $liabilities; // plug figure — see class-level caveat

        return response()->json([
            'note' => 'ERP Balance Sheet Summary, not a formal audited Balance Sheet — equity is a plug figure (assets − liabilities), not independently tracked. See API documentation.',
            'as_of' => $balances['as_of'],
            'assets' => [
                'receivables' => $balances['receivables'],
                'stock_value' => $balances['stock_value'],
                'cash_balance' => $balances['cash_balance'],
                'bank_balance' => $balances['bank_balance'],
                'total' => round($assets, 2),
            ],
            'liabilities' => [
                'payables' => $balances['payables'],
                'total' => round($liabilities, 2),
            ],
            'equity_approx' => round($equity, 2),
        ]);
    }
}