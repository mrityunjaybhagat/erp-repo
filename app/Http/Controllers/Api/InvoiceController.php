<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // GET /invoices?page=&limit=&search=&from=&to=&status=&customer_id=
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'items.product']);

        if ($search = $request->query('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
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

        $data = $query->orderByDesc('id')->skip($offset)->take($limit)->get()
            ->map(fn ($inv) => $this->withSummaries($inv));

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    // GET /invoices/{id} — raw object, matching your confirmed real shape
    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items.product', 'payments']);
        return response()->json($this->withSummaries($invoice));
    }

    /**
     * POST /invoices
     * Expects: { customer_id, date, status, items: [{ product_id,
     * quantity, rate, gst_rate, mrp, discount }] }
     * Same computation as your real data confirms: subtotal =
     * quantity * rate; tax_amount = subtotal * gst_rate / 100; total ==
     * subtotal (pre-tax — matches your real data, not a bug). mrp and
     * discount are stored as given, not auto-derived from each other —
     * your real data doesn't cleanly fit a fixed formula between them.
     * Decrements product stock for every line (this is a sale).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'status' => 'nullable|in:Unpaid,Paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0',
            'items.*.mrp' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'date' => $validated['date'],
                'customer_id' => $validated['customer_id'],
                'status' => $validated['status'] ?? 'Unpaid',
            ]);

            $totalAmount = 0;
            $gstAmount = 0;

            foreach ($validated['items'] as $line) {
                $subtotal = $line['quantity'] * $line['rate'];
                $tax = round($subtotal * $line['gst_rate'] / 100, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'],
                    'mrp' => $line['mrp'] ?? 0,
                    'rate' => $line['rate'],
                    'discount' => $line['discount'] ?? 0,
                    'quantity' => $line['quantity'],
                    'gst_rate' => $line['gst_rate'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'total' => $subtotal,
                ]);

                $product = Product::findOrFail($line['product_id']);
                StockMovement::record($product, 'sale', -$line['quantity'], 'Invoice', $invoice->id);

                $totalAmount += $subtotal;
                $gstAmount += $tax;
            }

            $invoice->update([
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'grand_total' => $totalAmount + $gstAmount,
            ]);

            return $invoice;
        });

        $invoice->load(['customer', 'items.product']);
        return response()->json($this->withSummaries($invoice), 201);
    }

    /**
     * PUT /invoices/{id}
     * Same replace-all-lines approach as PurchaseController::update —
     * reverses old stock movements before applying new ones, so stock
     * never drifts no matter what changed.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'status' => 'nullable|in:Unpaid,Paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0',
            'items.*.mrp' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            foreach ($invoice->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    StockMovement::record($product, 'adjustment', $oldItem->quantity, 'Invoice', $invoice->id, 'Reversed on invoice edit');
                }
            }
            $invoice->items()->delete();

            $totalAmount = 0;
            $gstAmount = 0;

            foreach ($validated['items'] as $line) {
                $subtotal = $line['quantity'] * $line['rate'];
                $tax = round($subtotal * $line['gst_rate'] / 100, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $line['product_id'],
                    'mrp' => $line['mrp'] ?? 0,
                    'rate' => $line['rate'],
                    'discount' => $line['discount'] ?? 0,
                    'quantity' => $line['quantity'],
                    'gst_rate' => $line['gst_rate'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'total' => $subtotal,
                ]);

                $product = Product::findOrFail($line['product_id']);
                StockMovement::record($product, 'sale', -$line['quantity'], 'Invoice', $invoice->id, 'Applied on invoice edit');

                $totalAmount += $subtotal;
                $gstAmount += $tax;
            }

            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'date' => $validated['date'],
                'status' => $validated['status'] ?? $invoice->status,
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'grand_total' => $totalAmount + $gstAmount,
            ]);
        });

        $invoice->load(['customer', 'items.product']);
        return response()->json($this->withSummaries($invoice));
    }

    // DELETE /invoices/{id} — reverses the stock it decremented
    public function destroy(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    StockMovement::record($product, 'adjustment', $item->quantity, 'Invoice', $invoice->id, 'Reversed on invoice delete');
                }
            }
            $invoice->delete();
        });

        return response()->json(null, 204);
    }

    private function withSummaries(Invoice $invoice): array
    {
        $array = $invoice->toArray();
        $array['gst_summary'] = $invoice->gstSummary();
        $array['hsn_summary'] = $invoice->hsnSummary();
        return $array;
    }

    // Matches the observed real pattern: letter + zero-padded number
    // (e.g. "A000407"). Adjust the prefix/reset logic if your real
    // numbering resets yearly or differs in some other way.
    private function nextInvoiceNumber(): string
    {
        $next = (Invoice::max('id') ?? 0) + 1;
        return 'A' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
