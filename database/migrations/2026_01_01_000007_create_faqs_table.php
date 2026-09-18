<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FAQ content — matches the categories already established on the
 * website (src/data/faqs.js) and the Flutter app (faq_data.dart):
 * Booking, Pricing, Shipment Tracking, Pickup & Delivery, Commercial
 * Stock, Billing / Documents, Support.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
