<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class CartFilters extends Filters
{

    protected $filters = ['personal', 'vendor_id', 'user_type', 'rate_type'];

    public function vendor_id($value)
    {
        $this->builder->where("vendor_id", $value);
    }

    public function user_type($value)
    {
        $this->builder->where("user_type", $value);
    }

    public function rate_type($value)
    {
        $this->builder->where("rate_type", $value);
    }

    public function personal($value)
    {
        $this->builder->where("user_id", $value);
    }
}
