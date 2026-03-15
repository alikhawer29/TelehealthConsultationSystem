<?php

namespace App\Filters\Shelter;

use App\Core\Abstracts\Filters;

class CountryFilters extends Filters
{

    protected $filters = ['search', 'states', 'parent_id'];

    public function search($value)
    {
        $this->builder->where("name", $value);
    }

    public function parent_id($value)
    {
        $this->builder->where("parent_id", $value);
    }


    public function states()
    {
        $this->builder->having('states_count', '>', 0);
    }
}
