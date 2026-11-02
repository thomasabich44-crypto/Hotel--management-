<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roomTypes = collect([
            ['name' => 'Single', 'description' => 'Comfortable room for one guest.', 'capacity' => 1, 'base_rate' => 4500.00],
            ['name' => 'Double', 'description' => 'Spacious room with one queen bed.', 'capacity' => 2, 'base_rate' => 6500.00],
            ['name' => 'Suite', 'description' => 'Premium suite with a separate living area.', 'capacity' => 4, 'base_rate' => 11000.00],
        ])->mapWithKeys(fn (array $type) => [
            $type['name'] => RoomType::updateOrCreate(['name' => $type['name']], $type),
        ]);

        foreach ([
            ['room_number' => '101', 'type' => 'Single', 'floor' => 1],
            ['room_number' => '102', 'type' => 'Single', 'floor' => 1],
            ['room_number' => '201', 'type' => 'Double', 'floor' => 2],
            ['room_number' => '202', 'type' => 'Double', 'floor' => 2],
            ['room_number' => '301', 'type' => 'Suite', 'floor' => 3],
        ] as $room) {
            Room::updateOrCreate(
                ['room_number' => $room['room_number']],
                ['room_type_id' => $roomTypes[$room['type']]->id, 'floor' => $room['floor'], 'status' => 'available']
            );
        }

        foreach ([
            ['name' => 'Breakfast', 'description' => 'Daily continental breakfast.', 'price' => 500.00],
            ['name' => 'Airport transfer', 'description' => 'One-way transfer to the airport.', 'price' => 1500.00],
            ['name' => 'Laundry service', 'description' => 'Standard laundry service per stay.', 'price' => 700.00],
        ] as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }

        $guests = collect([
            ['first_name' => 'Mekdes', 'last_name' => 'Abebe', 'email' => 'mekdes.abebe@example.test', 'phone' => '+251 911 234 501'],
            ['first_name' => 'Dawit', 'last_name' => 'Tesfaye', 'email' => 'dawit.tesfaye@example.test', 'phone' => '+251 911 234 502'],
            ['first_name' => 'Hana', 'last_name' => 'Bekele', 'email' => 'hana.bekele@example.test', 'phone' => '+251 911 234 503'],
        ])->mapWithKeys(fn (array $guest) => [
            $guest['email'] => Guest::updateOrCreate(['email' => $guest['email']], $guest),
        ]);

        $today = today();
        $reservations = [
            ['code' => 'DEMO1001', 'guest' => 'mekdes.abebe@example.test', 'room' => '101', 'start' => $today->copy()->addDays(2), 'end' => $today->copy()->addDays(4), 'status' => 'confirmed'],
            ['code' => 'DEMO1002', 'guest' => 'dawit.tesfaye@example.test', 'room' => '201', 'start' => $today->copy()->subDay(), 'end' => $today->copy()->addDay(), 'status' => 'checked_in'],
            ['code' => 'DEMO1003', 'guest' => 'hana.bekele@example.test', 'room' => '301', 'start' => $today->copy()->addDays(5), 'end' => $today->copy()->addDays(7), 'status' => 'confirmed'],
        ];

        foreach ($reservations as $sample) {
            $reservation = Reservation::updateOrCreate(
                ['confirmation_code' => $sample['code']],
                [
                    'guest_id' => $guests[$sample['guest']]->id,
                    'check_in' => $sample['start']->toDateString(),
                    'check_out' => $sample['end']->toDateString(),
                    'status' => $sample['status'],
                    'notes' => null,
                ]
            );
            $room = Room::where('room_number', $sample['room'])->firstOrFail();
            $reservation->rooms()->sync([
                $room->id => ['nightly_rate' => $room->roomType->base_rate],
            ]);
        }

        $paidReservation = Reservation::where('confirmation_code', 'DEMO1002')->firstOrFail();
        Payment::updateOrCreate(
            [
                'reservation_id' => $paidReservation->id,
                'method' => 'card',
                'paid_at' => $paidReservation->created_at->copy()->startOfDay(),
            ],
            ['amount' => 6500.00, 'status' => 'paid']
        );
    }
}
