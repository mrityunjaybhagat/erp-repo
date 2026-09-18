<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // GET /products?page=&limit=&search=&from=&to=
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $totalRecords = $query->count();
        $limit = max(1, (int) $request->query('limit', 15));
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $limit;

        $data = $query->orderByDesc('id')->skip($offset)->take($limit)->get();

        return response()->json(['data' => $data, 'totalRecords' => $totalRecords]);
    }

    // GET /products/{id}
    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'hsn_code' => 'nullable|string|max:20',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'mrp' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'hsn_code' => 'nullable|string|max:20',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'mrp' => 'nullable|numeric|min:0',
            'rate' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }
}
