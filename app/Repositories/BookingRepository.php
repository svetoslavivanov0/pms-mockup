<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\BaseDTO;
use App\DTOs\Booking\BookingDTO;
use App\Models\Booking;

class BookingRepository implements RepositoryInterface
{
    /**
     * @param BookingDTO $data
     * @return Booking
     */
    public function updateOrCreate(BaseDTO $data): Booking
    {
        return Booking::updateOrCreate(
            [
                'external_id' => $data->externalId
            ],
            [
                'external_id' => $data->externalId,
                'arrival_date' => $data->arrivalDate,
                'departure_date' => $data->departureDate,
                'room_id' => $data->roomId,
                'room_type_id' => $data->roomTypeId,
                'status' => $data->status,
                'notes' => $data->notes,
            ]
        );
    }

    public function syncGuests(Booking $booking, array $guests): Booking
    {
        $booking->guests()->sync($guests);

        $booking->refresh();

        return $booking;
    }
}
