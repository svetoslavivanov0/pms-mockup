<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\BaseDTO;
use App\DTOs\Room\RoomDTO;
use App\Models\Room;

class RoomRepository implements RepositoryInterface
{
    /**
     * @param RoomDTO $data
     * @return Room
     */
    public function updateOrCreate(BaseDTO $data): Room
    {
        return Room::updateOrCreate([
            'external_id' => $data->externalId,
        ], [
            'external_id' => $data->externalId,
            'number' => $data->number,
            'floor' => $data->floor,
        ]);
    }
}
