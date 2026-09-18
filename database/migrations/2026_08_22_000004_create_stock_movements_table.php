<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CHANGED from the original version of this file: it used to also run
// Schema::table('products', ...) to add a current_stock column, back
// when I assumed products already existed. Since products didn't exist
// yet (that's what caused your migration error), current_stock is now
// created directly in 2026_08_21_000002_create_products_table.php
// instead — this file only creates stock_movements.
//
// A ledger of every stock change, not just a running total — this is
// what makes stock levels auditable. reference_type/reference_id is
// polymorphic: a movement from a Purchase points to that purchase, one
// from an Invoice (a sale) points to that invoice, manual corrections
// have both null.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return']);
            $table->integer('quantity_change');
            $table->integer('balance_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
