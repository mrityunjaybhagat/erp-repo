<?php

#namespace App\Models;
namespace App\Models\ShippingModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status values are the canonical vocabulary shared with both
 * frontends — see the migration's docblock. Keep this list in sync
 * with the website's ShipmentStatus usage and the Flutter
 * `ShipmentStatus` enum.
 */
class Shipment extends Model
{
    use HasFactory;

    public const STATUSES = [
        'booking_confirmed',
        'pickup_completed',
        'in_transit',
        'arrived_at_destination',
        'out_for_delivery',
        'delivered',
    ];

    protected $fillable = [
        'customer_id',
        'awb',
        'origin_city',
        'destination_city',
        'parcel_type',
        'weight',
        'packages',
        'status',
        'booking_date',
        'expected_delivery',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class)->orderBy('sort_order');
    }

    public static function generateAwb(): string
    {
        do {
            $awb = 'DP' . random_int(10000000, 99999999);
        } while (self::where('awb', $awb)->exists());

        return $awb;
    }
}
