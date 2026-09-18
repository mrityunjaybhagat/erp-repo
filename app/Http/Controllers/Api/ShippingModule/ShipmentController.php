<?php

namespace App\Http\Controllers\Api\ShippingModule;

use App\Http\Controllers\Controller;
use App\Models\ShippingModule\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bookings + tracking. Booking (index/store/show) requires auth:sanctum
 * — matches the website's Book Shipment flow and the Flutter app's
 * BookShipmentScreen / BookingConfirmationScreen, both of which only
 * run after OTP verification. Tracking (track) is public, since anyone
 * with an AWB can check status on either frontend without logging in.
 */
class ShipmentController extends Controller
{
    /** GET /api/shipments — the authenticated customer's own shipments. */
    public function index(Request $request): JsonResponse
    {
        $shipments = $request->user()
            ->shipments()
            ->with('trackingEvents')
            ->latest()
            ->get();

        return response()->json($shipments);
    }

    /**
     * POST /api/shipments
     * Same field set as the website's hero "Book a Shipment" panel /
     * `/quote` page and the Flutter BookShipmentScreen: Pickup City,
     * Delivery City, Parcel / Stock Type, Approximate Weight, Number
     * of Packages, Notes.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin_city' => ['required', 'string', 'max:120'],
            'destination_city' => ['required', 'string', 'max:120'],
            'parcel_type' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:50'],
            'packages' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipment = $request->user()->shipments()->create([
            ...$data,
            'awb' => Shipment::generateAwb(),
            'status' => 'booking_confirmed',
            'booking_date' => now()->toDateString(),
        ]);

        $shipment->trackingEvents()->create([
            'label' => 'Booking Confirmed',
            'location' => $data['origin_city'],
            'event_time' => now()->toDateTimeString(),
            'completed' => true,
            'sort_order' => 0,
        ]);

        return response()->json($shipment->load('trackingEvents'), 201);
    }

    /** GET /api/shipments/{shipment} — must belong to the authenticated customer. */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        if ($shipment->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($shipment->load('trackingEvents'));
    }

    /**
     * GET /api/track/{awb} — public, no auth required.
     * Matches the website's /track page and the Flutter app's Track tab.
     */
    public function track(string $awb): JsonResponse
    {
        $shipment = Shipment::where('awb', $awb)->with('trackingEvents')->first();

        if (! $shipment) {
            return response()->json(['message' => 'No shipment found for that AWB number'], 404);
        }

        return response()->json($shipment);
    }
}
