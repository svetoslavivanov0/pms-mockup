<?php

declare(strict_types=1);

namespace App\DTOs\Guest;

use App\DTOs\BaseDTO;

class GuestDTO implements BaseDTO
{
    public int $externalId;
    public string $firstName;
    public string $lastName;
    public string $email;

    public function __construct(array $data)
    {
        $this->externalId = $data['id'];
        $this->firstName = $data['first_name'];
        $this->lastName = $data['last_name'];
        $this->email = $data['email'];
    }
}
