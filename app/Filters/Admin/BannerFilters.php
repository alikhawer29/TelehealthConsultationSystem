<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class BannerFilters extends Filters
{

    protected $filters = ['status', 'from', 'to'];


    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }
}
