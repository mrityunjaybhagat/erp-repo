<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mirrors payments.php exactly, but for money going OUT to a supplier
// against a Purchase, instead of coming IN from a customer against an
// Invoice. Kept as a separate table rather than making `payments`
// polymorphic, since payments may already have real test data by now
// and altering its shape is riskier than adding a new table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'Other'])->default('Cash');
            $table->date('date');
            $table->string('reference_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
