<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NEW CONCEPT — no prior spec existed for this. Design reasoning:
// a Cash Voucher records a cash movement NOT tied to a formal
// Invoice/Purchase/Expense — e.g. petty cash given to an employee,
// a walk-in cash sale too small to invoice, cash received from
// somewhere ad hoc. `type` distinguishes money in (Receipt) from
// money out (Payment) — same in/out concept as Payment, but for
// movements that don't have an Invoice or Purchase behind them.
// `party_name` is plain text, not a Customer/Supplier FK, since a
// cash voucher's other party often isn't in either table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->enum('type', ['Receipt', 'Payment']);
            $table->string('party_name')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('narration')->nullable(); // purpose of the voucher
            $table->enum('payment_mode', ['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'Other'])->default('Cash');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_vouchers');
    }
};
