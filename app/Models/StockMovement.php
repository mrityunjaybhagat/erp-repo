<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'type', 'quantity_change', 'balance_after', 'reference_type', 'reference_id', 'date', 'notes'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public static function record(Product $product, string $type, int $quantityChange, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): self
    {
        $product->current_stock += $quantityChange;
        $product->save();

        return static::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'balance_after' => $product->current_stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'date' => now()->toDateString(),
            'notes' => $notes,
        ]);
    }
}
