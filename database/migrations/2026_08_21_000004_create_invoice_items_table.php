<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Matches your real confirmed invoice item data exactly: mrp, rate,
// discount, quantity, gst_rate, subtotal, tax_amount, total (where
// total == subtotal, pre-tax — confirmed from your real data, not
// a bug I'm introducing).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('rate', 10, 2);
            $table->decimal('discount', 5, 2)->default(0); // percent
            $table->unsignedInteger('quantity');
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
