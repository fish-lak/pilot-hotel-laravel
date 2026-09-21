<?php

namespace App\Support;

use App\Models\Reservation;
use App\Services\NoShowService;

class RoomCatalog
{
    public static function all(): array
    {
        return [
            [
                'slug' => 'standard-room',
                'name' => 'Standard Room',
                'image' => 'images/rooms/standard-room.jpg',
                'rate_min' => 1999,
                'rate_max' => 2500,
                'capacity' => '2 pax',
                'max_guests' => 2,
                'description' => 'Regular room accommodation',
                'amenities' => ['Basic amenities'],
                'locations' => 'Cabins 5–8',
                'room_numbers' => ['Cabin 5', 'Cabin 6', 'Cabin 7', 'Cabin 8'],
                'units' => 4,
            ],
            [
                'slug' => 'deluxe-room',
                'name' => 'Deluxe Room',
                'image' => 'images/rooms/deluxe-room.jpg',
                'rate_min' => 2499,
                'rate_max' => 3500,
                'capacity' => '2 pax',
                'max_guests' => 2,
                'description' => 'Comfortable room with a flexible bed setup',
                'amenities' => ['Queen bed', '2 single beds', 'Basic amenities'],
                'locations' => 'Cabins 1–2',
                'room_numbers' => ['Cabin 1', 'Cabin 2'],
                'units' => 2,
            ],
            [
                'slug' => 'super-deluxe-room',
                'name' => 'Super Deluxe Room',
                'image' => 'images/rooms/super-deluxe-room.jpg',
                'rate_min' => 2999,
                'rate_max' => 3500,
                'capacity' => '2 pax',
                'max_guests' => 2,
                'description' => 'A more complete stay for longer visits',
                'amenities' => ['Complete amenities', 'Mini refrigerator', 'Coffee and tea facility'],
                'locations' => 'Rooms 201–207',
                'room_numbers' => ['Room 201', 'Room 202', 'Room 203', 'Room 204', 'Room 205', 'Room 206', 'Room 207'],
                'units' => 7,
            ],
            [
                'slug' => 'family-room',
                'name' => 'Family Room',
                'image' => 'images/rooms/family-room.jpg',
                'rate_min' => 3999,
                'rate_max' => 5000,
                'capacity' => '4 pax',
                'max_guests' => 4,
                'description' => 'Ideal for families travelling together',
                'amenities' => ['Spacious family setup'],
                'locations' => 'Rooms 101–104',
                'room_numbers' => ['Room 101', 'Room 102', 'Room 103', 'Room 104'],
                'units' => 4,
            ],
            [
                'slug' => 'barkada-room',
                'name' => 'Barkada Room',
                'image' => 'images/rooms/barkada-room.jpg',
                'rate_min' => null,
                'rate_max' => null,
                'capacity' => '9–10 pax',
                'max_guests' => 10,
                'description' => 'Ideal for groups and barkadas',
                'amenities' => ['Group accommodation'],
                'locations' => 'Cabins 3, 4 and 10',
                'room_numbers' => ['Cabin 3', 'Cabin 4', 'Cabin 10'],
                'units' => 3,
            ],
        ];
    }

    public static function find(string $name): ?array
    {
        return collect(self::all())->first(fn (array $room) => str_starts_with($name, $room['name']));
    }

    public static function roomOptions(): array
    {
        return collect(self::all())->flatMap(fn (array $room) => collect($room['room_numbers'])->map(fn (string $number) => [
            'value' => $room['name'] . ' - ' . $number,
            'room' => $number,
            'type' => $room['name'],
        ]))->all();
    }

    public static function withAvailability(): array
    {
        app(NoShowService::class)->markExpired();
        $booked = Reservation::query()
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->get(['room']);

        return collect(self::all())->map(function (array $room) use ($booked) {
            $typeBookings = $booked->filter(fn (Reservation $reservation) => $reservation->room === $room['name'])->count();
            $room['room_statuses'] = collect($room['room_numbers'])->map(function (string $number, int $index) use ($booked, $room, $typeBookings) {
                $label = $room['name'] . ' - ' . $number;
                $bookedDirectly = $booked->contains(fn (Reservation $reservation) => $reservation->room === $label);
                return ['number' => $number, 'available' => !$bookedDirectly && $index >= $typeBookings];
            })->all();
            $room['available_units'] = collect($room['room_statuses'])->where('available', true)->count();
            return $room;
        })->all();
    }
}