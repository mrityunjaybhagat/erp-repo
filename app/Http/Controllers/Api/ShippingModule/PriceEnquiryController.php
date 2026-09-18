<?php

namespace App\Http\Controllers\Api\ShippingModule;

use App\Http\Controllers\Controller;
use App\Models\ShippingModule\PriceEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Check Price" — matches the website's Quick Price Check / hero form
 * and the Flutter Check Price flow. Public endpoint (mobile number is
 * collected and verified via OTP by the frontend before calling this,
 * or afterward for logged-in customers), but attaches customer_id
 * automatically when a valid Sanctum token is present.
 *
 * The fare here is a placeholder calculation only — replace
 * `estimate()` with real pricing logic (rate cards, distance lookup,
 * etc.) when that's ready. Every enquiry is stored regardless, since
 * this is also Dev Parcel's lead-generation mechanism.
 */
class PriceEnquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10'],
            'pickup_city' => ['required', 'string', 'max:120'],
            'delivery_city' => ['required', 'string', 'max:120'],
            'goods_type' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:50'],
            'packages' => ['nullable', 'integer', 'min:1'],
        ]);

        [$min, $max] = $this->estimate($data['weight'] ?? null);

        $enquiry = PriceEnquiry::create([
            ...$data,
            'customer_id' => $request->user('sanctum')?->id,
            'estimated_min' => $min,
            'estimated_max' => $max,
        ]);

        return response()->json($enquiry, 201);
    }

    /**
     * Placeholder fare calculation: a base range nudged by weight if a
     * numeric value can be parsed out of it (e.g. "50 kg" -> 50).
     * Replace with real pricing logic later.
     */
    private function estimate(?string $weight): array
    {
        $base = 850;
        $kg = 0;
        if ($weight && preg_match('/(\d+(\.\d+)?)/', $weight, $matches)) {
            $kg = (float) $matches[1];
        }

        $min = (int) round($base + $kg * 3);
        $max = (int) round($min * 1.2);

        return [$min, $max];
    }
}
