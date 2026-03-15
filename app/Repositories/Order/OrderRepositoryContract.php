<?php

namespace App\Repositories\Order;

use App\Core\Abstracts\Filters;
use Illuminate\Database\Eloquent\Model;

interface OrderRepositoryContract
{
    public function createPetOrder(array $params, $pet);
}
