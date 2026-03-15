<?php

namespace App\Filters\Admin;
use App\Core\Abstracts\Filters;

class ReviewFilters extends Filters {

    protected $filters = ['product_id'];

    public function product_id($value)
    {
        $this->builder->where("product_id", $value);
    }
}
