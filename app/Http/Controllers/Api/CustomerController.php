<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // GET /customers?page=&limit=&search=&from=&to=
    public function index(Request $request)
    {
        $query = Customer::query();

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

    // GET /customers/{id}
    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:20',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:20',
        ]);

        $customer->update($validated);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(null, 204);
    }
}
