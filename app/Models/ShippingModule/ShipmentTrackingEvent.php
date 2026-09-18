<?php

// namespace App\Models;
namespace App\Models\ShippingModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'label',
        'location',
        'event_time',
        'completed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
