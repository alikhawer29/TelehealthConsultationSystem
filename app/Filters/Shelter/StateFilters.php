<?php

namespace App\Filters\Shelter;

use App\Core\Abstracts\Filters;

class StateFilters extends Filters
{

    protected $filters = ['search', 'country_id', 'cities', 'order'];

    public function country_id($value)
    {
        $this->builder->where('country_id', $value);
    }

    public function cities()
    {
        $this->builder->having('cities_count', '>', 0);
    }

    public function search($value)
    {
        $this->builder->where("status", $value);
    }

    public function order($value)
    {
        $this->builder->orderBy("name", 'ASC');
    }
}
