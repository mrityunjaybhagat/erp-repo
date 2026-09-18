<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ASSUMED SHAPE — no real /expenses sample was ever confirmed (the
// fetch was blocked both times it was tried). `category` is a plain
// string, not an enum, on purpose: furniture/utilities/rent/etc. are
// business categories you'll likely want to add to without a migration
// every time. If your real API differs, this is a quick fix.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // e.g. Furniture, Utilities, Rent, Stationery
            $table->string('vendor_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_mode', ['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'Other'])->default('Cash');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
