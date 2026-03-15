<?php

namespace App\Filters\Shelter;

use App\Core\Abstracts\Filters;

class CityFilters extends Filters
{

    protected $filters = ['search', 'state_id', 'only'];

    public function search1($value)
    {
        $this->builder
            ->addSelect([
                \DB::raw('(SELECT CONCAT(name,", ",state_name,", ",country_name)) as raw_name'),
            ])
            ->where(
                fn($q) =>
                $q->where("name", 'like', `%` . $value . '%')
                    ->orWhereRelation('country', 'name', 'like', `%` . $value . '%')
                    ->orWhereRelation('state', 'name', 'like', `%` . $value . '%')
            );
    }

    public function search($value)
    {
        $this->builder
            ->select([
                'cities.id',
                'cities.name as name',
                'cities.state_id',
                'cities.country_id',
                'cities.latitude',
                'cities.longitude',
                'cities.created_at',
                'cities.updated_at',
                'cities.flag',
                'cities.wikiDataId',
                'countries.name as country_name',
                'states.name as state_name',
                \DB::raw('CONCAT(cities.name, ", ", states.name, ", ", countries.name) as raw_name')
            ])
            ->leftJoin('states', 'cities.state_id', '=', 'states.id')
            ->leftJoin('countries', 'cities.country_id', '=', 'countries.id')
            ->where(function ($query) use ($value) {
                $query->where('cities.name', 'like', '%' . $value . '%')
                    ->orWhere('states.name', 'like', '%' . $value . '%')
                    ->orWhere('countries.name', 'like', '%' . $value . '%');
            });
    }

    public function search11($value)
    {
        $this->builder
            ->select([
                'cities.id',
                'cities.name as name',
                'cities.state_id',
                'cities.country_id',
                'cities.latitude',
                'cities.longitude',
                'cities.created_at',
                'cities.updated_at',
                'cities.flag',
                'cities.wikiDataId',
                'countries.name as country_name',
                'states.name as state_name',
                \DB::raw('CONCAT(cities.name, ", ", states.name, ", ", countries.name) as raw_name')

            ])
            ->leftJoin('states', 'cities.state_id', '=', 'states.id')
            ->leftJoin('countries', 'cities.country_id', '=', 'countries.id')
            ->where('states.name', 'like', '%' . $value . '%'); // Strictly search by state name
    }




    public function state_id($value)
    {
        $this->builder->where('state_id', $value);
    }

    public function only($value)
    {
        $this->builder->limit($value);
    }
}
