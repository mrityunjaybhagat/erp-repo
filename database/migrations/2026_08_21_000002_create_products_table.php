<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fields match the real /products API response: id, name, hsn_code,
// gst_rate, mrp, rate, reorder_level, description, created_at, updated_at.
// `current_stock` is included directly here (not as a separate ALTER
// TABLE migration) since this table didn't exist yet — see the note in
// stock_movements' migration file about the part I removed from it.
//
// This is the exact table purchase_items.product_id and
// stock_movements.product_id were trying to reference — that's why
// their migration failed with "Cannot add foreign key constraint" (SQL
// error 1215): the table those foreign keys point to didn't exist.
// $table->id() creates a standard unsigned bigint, matching what
// foreignId('product_id') expects on the other end, so once this runs
// first, those two migrations should succeed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hsn_code')->nullable();
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->text('description')->nullable();
            $table->integer('current_stock')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
