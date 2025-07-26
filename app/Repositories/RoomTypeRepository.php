<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\BaseDTO;
use App\DTOs\RoomType\RoomTypeDTO;
use App\Models\RoomType;

class RoomTypeRepository implements RepositoryInterface
{
    /**
     * @param RoomTypeDTO $data
     * @return RoomType
     */
    public function updateOrCreate(BaseDTO $data): RoomType
    {
        return RoomType::updateOrCreate([
            'external_id' => $data->externalId,
        ], [
            'external_id' => $data->externalId,
            'name' => $data->name,
            'description' => $data->description,
        ]);
    }
}
