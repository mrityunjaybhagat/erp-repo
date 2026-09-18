<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // GET /stock?page=&limit=&search=&low=1
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->boolean('low')) {
            $query->whereColumn('current_stock', '<=', 'reorder_level');
        }

        $totalRecords = $query->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $data = $query->orderBy('name')->skip($offset)->take($limit)
            ->get(['id', 'name', 'hsn_code', 'current_stock', 'reorder_level']);

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    public function movements(Product $product)
    {
        $movements = StockMovement::where('product_id', $product->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $movements]);
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity_change' => 'required|integer|not_in:0',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $movement = StockMovement::record($product, 'adjustment', $validated['quantity_change'], null, null, $validated['notes'] ?? null);

        return response()->json($movement, 201);
    }
}
