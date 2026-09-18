<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Submissions from the website's /contact page and the Flutter app's
 * Contact Us screen. AWB is optional — used when the enquiry is about
 * a specific shipment.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('mobile', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('awb', 20)->nullable();
            $table->text('message');
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
