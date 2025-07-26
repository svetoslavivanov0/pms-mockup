<?php

declare(strict_types=1);

namespace App\DTOs\Room;

use App\DTOs\BaseDTO;

class RoomDTO implements BaseDTO
{
    public int $externalId;
    public int $number;
    public int $floor;

    public function __construct(array $data) {
        $this->externalId = (int)$data['id'];
        $this->number = (int)$data['number'];
        $this->floor = (int)$data['floor'];
    }
}
