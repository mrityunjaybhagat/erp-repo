<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ⚠️ DO NOT RUN THIS MIGRATION YET.
// This has a foreign key to `invoices`, and you're leaving Invoices for
// now — if you migrate this before an invoices table exists, you'll
// hit the exact same "Cannot add foreign key constraint" error again,
// for the same reason (the referenced table doesn't exist yet).
// Either delete this file until Invoices is built, or just don't run
// `php artisan migrate` while this file is present without an
// invoices table already there.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'Other'])->default('Cash');
            $table->date('date');
            $table->string('reference_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
