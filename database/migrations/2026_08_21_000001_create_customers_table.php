<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fields match the real /customers API response you confirmed earlier:
// id, name, email, phone, address, gstin, created_at, updated_at.
// Timestamped to run BEFORE suppliers/purchases/stock_movements, since
// none of those actually depend on this table — this is just Customers
// standing on its own, in case Invoices (which does depend on it) gets
// built later and needs it to already exist.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('gstin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
