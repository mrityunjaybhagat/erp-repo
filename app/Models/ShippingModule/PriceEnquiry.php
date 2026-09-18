<?php

// namespace App\Models;
namespace App\Models\ShippingModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'mobile',
        'pickup_city',
        'delivery_city',
        'goods_type',
        'weight',
        'packages',
        'estimated_min',
        'estimated_max',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
