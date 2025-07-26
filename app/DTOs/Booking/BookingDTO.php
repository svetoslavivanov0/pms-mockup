<?php

declare(strict_types=1);

namespace App\DTOs\Booking;

use App\DTOs\BaseDTO;
use Carbon\Carbon;

class BookingDTO implements BaseDTO
{
    public string $externalId;
    public int $roomId;
    public int $roomTypeId;
    public string $status;
    public ?string $notes = null;
    public array $guestIds;
    public Carbon $arrivalDate;
    public Carbon $departureDate;

    public function __construct(array $data) {
        $this->externalId = (string)$data['external_id'];
        $this->roomId = (int)$data['room_id'];
        $this->roomTypeId = (int)$data['room_type_id'];
        $this->status = $data['status'];
        $this->notes = $data['notes'] ?? null;
        $this->guestIds = $data['guest_ids'];
        $this->arrivalDate = Carbon::parse($data['arrival_date']);
        $this->departureDate = Carbon::parse($data['departure_date']);
    }
}
