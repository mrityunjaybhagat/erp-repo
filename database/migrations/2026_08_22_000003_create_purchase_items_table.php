<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Dropped `mrp`/`discount` from the invoice_items shape — those are
// retail concepts that don't map onto buying stock from a supplier.
// `total` == `subtotal` (pre-tax), matching your real invoice_items data.
//
// This is the migration that failed with error 1215 before — it needs
// the products table (2026_08_21_000002) to exist first, which is why
// that migration is timestamped earlier.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->decimal('rate', 10, 2);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
