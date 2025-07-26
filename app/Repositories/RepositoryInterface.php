<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\BaseDTO;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function updateOrCreate(BaseDTO $data): Model;
}
