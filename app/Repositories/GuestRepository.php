<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\BaseDTO;
use App\DTOs\Guest\GuestDTO;
use App\Models\Guest;

class GuestRepository implements RepositoryInterface
{
    /**
     * @param GuestDTO $data
     * @return Guest
     */
    public function updateOrCreate(BaseDTO $data): Guest
    {
        return Guest::updateOrCreate([
            'external_id' => $data->externalId
        ], [
            'external_id' => $data->externalId,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
        ]);
    }
}
