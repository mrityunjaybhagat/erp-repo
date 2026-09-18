<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    // GET /purchases?page=&limit=&search=&from=&to=
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'items.product']);

        if ($search = $request->query('search')) {
            $query->where('purchase_number', 'like', "%{$search}%");
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
            ->map(fn ($p) => $this->withGstSummary($p));

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);
        return response()->json($this->withGstSummary($purchase));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'status' => 'nullable|in:Unpaid,Paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0',
        ]);

        $purchase = DB::transaction(function () use ($validated) {
            $purchase = Purchase::create([
                'purchase_number' => $this->nextPurchaseNumber(),
                'date' => $validated['date'],
                'supplier_id' => $validated['supplier_id'],
                'status' => $validated['status'] ?? 'Unpaid',
            ]);

            $totalAmount = 0;
            $gstAmount = 0;

            foreach ($validated['items'] as $line) {
                $subtotal = $line['quantity'] * $line['rate'];
                $tax = round($subtotal * $line['gst_rate'] / 100, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'rate' => $line['rate'],
                    'gst_rate' => $line['gst_rate'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'total' => $subtotal,
                ]);

                $product = Product::findOrFail($line['product_id']);
                StockMovement::record($product, 'purchase', $line['quantity'], 'Purchase', $purchase->id);

                $totalAmount += $subtotal;
                $gstAmount += $tax;
            }

            $purchase->update([
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'grand_total' => $totalAmount + $gstAmount,
            ]);

            return $purchase;
        });

        $purchase->load(['supplier', 'items.product']);
        return response()->json($this->withGstSummary($purchase), 201);
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'status' => 'nullable|in:Unpaid,Paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            foreach ($purchase->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    StockMovement::record($product, 'adjustment', -$oldItem->quantity, 'Purchase', $purchase->id, 'Reversed on purchase edit');
                }
            }
            $purchase->items()->delete();

            $totalAmount = 0;
            $gstAmount = 0;

            foreach ($validated['items'] as $line) {
                $subtotal = $line['quantity'] * $line['rate'];
                $tax = round($subtotal * $line['gst_rate'] / 100, 2);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'rate' => $line['rate'],
                    'gst_rate' => $line['gst_rate'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'total' => $subtotal,
                ]);

                $product = Product::findOrFail($line['product_id']);
                StockMovement::record($product, 'purchase', $line['quantity'], 'Purchase', $purchase->id, 'Applied on purchase edit');

                $totalAmount += $subtotal;
                $gstAmount += $tax;
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'status' => $validated['status'] ?? $purchase->status,
                'total_amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'grand_total' => $totalAmount + $gstAmount,
            ]);
        });

        $purchase->load(['supplier', 'items.product']);
        return response()->json($this->withGstSummary($purchase));
    }

    public function destroy(Purchase $purchase)
    {
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    StockMovement::record($product, 'adjustment', -$item->quantity, 'Purchase', $purchase->id, 'Reversed on purchase delete');
                }
            }
            $purchase->delete();
        });

        return response()->json(null, 204);
    }

    private function withGstSummary(Purchase $purchase): array
    {
        $array = $purchase->toArray();
        $array['gst_summary'] = $purchase->gstSummary();
        return $array;
    }

    private function nextPurchaseNumber(): string
    {
        $next = (Purchase::max('id') ?? 0) + 1;
        return 'P' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
