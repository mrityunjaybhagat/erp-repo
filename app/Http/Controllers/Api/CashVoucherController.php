<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashVoucher;
use Illuminate\Http\Request;

class CashVoucherController extends Controller
{
    // GET /cash-vouchers?page=&limit=&search=&from=&to=&type=
    public function index(Request $request)
    {
        $query = CashVoucher::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('party_name', 'like', "%{$search}%");
            });
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type); // Receipt | Payment
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

    public function show(CashVoucher $cashVoucher)
    {
        return response()->json($cashVoucher);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Receipt,Payment',
            'party_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'narration' => 'nullable|string',
            'payment_mode' => 'required|in:Cash,Bank Transfer,UPI,Cheque,Other',
            'date' => 'required|date',
        ]);

        $validated['voucher_number'] = $this->nextVoucherNumber();
        $voucher = CashVoucher::create($validated);

        return response()->json($voucher, 201);
    }

    public function update(Request $request, CashVoucher $cashVoucher)
    {
        $validated = $request->validate([
            'type' => 'sometimes|required|in:Receipt,Payment',
            'party_name' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'narration' => 'nullable|string',
            'payment_mode' => 'sometimes|required|in:Cash,Bank Transfer,UPI,Cheque,Other',
            'date' => 'sometimes|required|date',
        ]);

        $cashVoucher->update($validated);

        return response()->json($cashVoucher);
    }

    public function destroy(CashVoucher $cashVoucher)
    {
        $cashVoucher->delete();
        return response()->json(null, 204);
    }

    private function nextVoucherNumber(): string
    {
        $next = (CashVoucher::max('id') ?? 0) + 1;
        return 'CV' . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
