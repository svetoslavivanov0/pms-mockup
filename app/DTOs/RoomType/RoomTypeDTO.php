<?php

declare(strict_types=1);

namespace App\DTOs\RoomType;

use App\DTOs\BaseDTO;

class RoomTypeDTO implements BaseDto
{
    public int $externalId;
    public string $name;
    public string $description;

    public function __construct(array $data)
    {
        $this->externalId = (int)$data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
    }
}
