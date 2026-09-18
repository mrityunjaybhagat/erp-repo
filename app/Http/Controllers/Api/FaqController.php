<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/faqs?category=Booking&q=tracking
 * Matches the website's /faq page and the Flutter FaqScreen, both of
 * which support category filtering and free-text search.
 */
class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Faq::query()->orderBy('sort_order');

        if ($category = $request->string('category')->toString()) {
            if ($category !== 'All') {
                $query->where('category', $category);
            }
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }
}
