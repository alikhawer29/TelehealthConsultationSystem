<?php

namespace App\Repositories\Cart;

use App\Core\Abstracts\Filters;

interface CartRepositoryContract
{
    public function updateOrCreate(array $conditionalParams,array $newParams);

    public function deleteAll(Filters|null $filter = null);
}
