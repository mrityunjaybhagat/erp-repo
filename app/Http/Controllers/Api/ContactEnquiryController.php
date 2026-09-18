<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Matches the website's /contact page and the Flutter app's Contact Us
 * screen: Name, Mobile, Email, optional AWB, Message. Public endpoint,
 * but attaches customer_id automatically when a valid Sanctum token is
 * present.
 */
class ContactEnquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'awb' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $enquiry = ContactEnquiry::create([
            ...$data,
            'customer_id' => $request->user('sanctum')?->id,
        ]);

        return response()->json($enquiry, 201);
    }
}
