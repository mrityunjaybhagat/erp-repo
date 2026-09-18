<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Seeds the exact FAQ content already hardcoded on both frontends
 * (src/data/faqs.js on the website, faq_data.dart in the Flutter app).
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'Booking',
                'question' => 'How do I book a parcel?',
                'answer' => 'Share your pickup city, delivery city and parcel details through Check Price or Book Shipment. We confirm the booking and arrange a pickup window.',
            ],
            [
                'category' => 'Pickup & Delivery',
                'question' => 'Which cities do you deliver to?',
                'answer' => 'Dev Parcel operates on an intercity logistics network covering major business hubs. Enter your pickup and delivery cities to confirm route availability.',
            ],
            [
                'category' => 'Commercial Stock',
                'question' => 'Can I transport commercial stock?',
                'answer' => 'Yes. Dev Parcel regularly moves stock, cartons and trade goods for wholesalers, distributors, manufacturers and retailers, in addition to individual parcels.',
            ],
            [
                'category' => 'Pricing',
                'question' => 'How is delivery pricing calculated?',
                'answer' => 'Pricing depends on factors such as weight, parcel type, distance between cities and delivery priority. Check Price gives an estimate; final pricing is confirmed by our team.',
            ],
            [
                'category' => 'Shipment Tracking',
                'question' => 'How can I track my shipment?',
                'answer' => 'Enter your AWB or tracking number on the Track page (or Track tab in the app) to see the current status and the full movement timeline for your consignment.',
            ],
            [
                'category' => 'Pickup & Delivery',
                'question' => 'What happens if delivery cannot be completed?',
                'answer' => 'If a delivery attempt is unsuccessful, our team will contact you to reschedule or arrange a return delivery back to the origin.',
            ],
            [
                'category' => 'Billing / Documents',
                'question' => 'Can I download my invoice or bilty?',
                'answer' => 'Invoices and the shipment bilty are available under Documents / Billing once a shipment is completed.',
            ],
            [
                'category' => 'Support',
                'question' => 'How do I contact support?',
                'answer' => 'Reach our logistics team from the Contact Us page — by phone, email, or a message with your AWB number if it relates to a specific shipment.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [...$faq, 'sort_order' => $index],
            );
        }
    }
}
