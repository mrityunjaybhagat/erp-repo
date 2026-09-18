<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ShippingModule\Shipment;
use Illuminate\Database\Seeder;

/**
 * Seeds the exact three shipments already hardcoded as mock data on
 * both frontends (src/data/tracking.js on the website,
 * mock_data.dart in the Flutter app) — same AWBs, same routes, same
 * statuses — so a fresh backend produces identical-looking data to
 * what people have already seen while the frontends were mocked.
 */
class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::where('phone', '9876543210')->first();

        $this->seedShipment($customer?->id, [
            'awb' => 'DP10293847',
            'origin_city' => 'Ahmedabad',
            'destination_city' => 'Pune',
            'parcel_type' => 'Commercial Cartons (12 pcs)',
            'weight' => '86 kg',
            'packages' => 12,
            'status' => 'in_transit',
            'booking_date' => '2026-08-28',
            'expected_delivery' => 'Tomorrow, by 6:00 PM',
        ], [
            ['label' => 'Booking Confirmed', 'location' => 'Ahmedabad', 'event_time' => '28 Aug, 10:12 AM', 'completed' => true],
            ['label' => 'Pickup Completed', 'location' => 'Ahmedabad', 'event_time' => '28 Aug, 04:40 PM', 'completed' => true],
            ['label' => 'In Transit', 'location' => 'Departed Ahmedabad Hub', 'event_time' => '29 Aug, 06:05 AM', 'completed' => true],
            ['label' => 'Arrived at Destination City', 'location' => 'Pune', 'event_time' => 'Expected 30 Aug', 'completed' => false],
            ['label' => 'Out for Delivery', 'location' => 'Pune', 'event_time' => 'Pending', 'completed' => false],
            ['label' => 'Delivered', 'location' => 'Pune', 'event_time' => 'Pending', 'completed' => false],
        ]);

        $this->seedShipment($customer?->id, [
            'awb' => 'DP10287213',
            'origin_city' => 'Surat',
            'destination_city' => 'Indore',
            'parcel_type' => 'Retail Stock',
            'weight' => '40 kg',
            'packages' => 6,
            'status' => 'delivered',
            'booking_date' => '2026-08-21',
            'expected_delivery' => 'Delivered 24 Aug',
        ], [
            ['label' => 'Booking Confirmed', 'location' => 'Surat', 'event_time' => '21 Aug, 09:20 AM', 'completed' => true],
            ['label' => 'Pickup Completed', 'location' => 'Surat', 'event_time' => '21 Aug, 03:10 PM', 'completed' => true],
            ['label' => 'In Transit', 'location' => 'Departed Surat Hub', 'event_time' => '22 Aug, 07:00 AM', 'completed' => true],
            ['label' => 'Arrived at Destination City', 'location' => 'Indore', 'event_time' => '23 Aug, 11:40 AM', 'completed' => true],
            ['label' => 'Out for Delivery', 'location' => 'Indore', 'event_time' => '24 Aug, 09:15 AM', 'completed' => true],
            ['label' => 'Delivered', 'location' => 'Indore', 'event_time' => '24 Aug, 01:30 PM', 'completed' => true],
        ]);

        $this->seedShipment($customer?->id, [
            'awb' => 'DP10281905',
            'origin_city' => 'Ahmedabad',
            'destination_city' => 'Jaipur',
            'parcel_type' => 'Cartons',
            'weight' => '58 kg',
            'packages' => 8,
            'status' => 'out_for_delivery',
            'booking_date' => '2026-08-30',
            'expected_delivery' => 'Today, by 8:00 PM',
        ], [
            ['label' => 'Booking Confirmed', 'location' => 'Ahmedabad', 'event_time' => '30 Aug, 08:45 AM', 'completed' => true],
            ['label' => 'Pickup Completed', 'location' => 'Ahmedabad', 'event_time' => '30 Aug, 02:00 PM', 'completed' => true],
            ['label' => 'In Transit', 'location' => 'Departed Ahmedabad Hub', 'event_time' => '31 Aug, 05:30 AM', 'completed' => true],
            ['label' => 'Arrived at Destination City', 'location' => 'Jaipur', 'event_time' => '31 Aug, 06:50 PM', 'completed' => true],
            ['label' => 'Out for Delivery', 'location' => 'Jaipur', 'event_time' => 'Today, 09:00 AM', 'completed' => true],
            ['label' => 'Delivered', 'location' => 'Jaipur', 'event_time' => 'Pending', 'completed' => false],
        ]);
    }

    private function seedShipment(?int $customerId, array $shipmentData, array $events): void
    {
        $shipment = Shipment::updateOrCreate(
            ['awb' => $shipmentData['awb']],
            [...$shipmentData, 'customer_id' => $customerId],
        );

        $shipment->trackingEvents()->delete();

        foreach ($events as $index => $event) {
            $shipment->trackingEvents()->create([...$event, 'sort_order' => $index]);
        }
    }
}
