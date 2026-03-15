<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class ServiceFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sort', 'year', 'role', 'speciality', 'branch', 'session_type', 'type'];

    public function speciality($value)
    {
        $valuesArray = explode(',', $value);
        $this->builder->where(function ($query) use ($valuesArray) {
            foreach ($valuesArray as $value) {
                $query->orwhere('professional', $value);
            }
        });
    }

    public function session_type($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->whereIn('id', function ($subQuery) use ($value) {
                $subQuery->select('user_id')
                    ->from('session_type')
                    ->where('session_type', 'like', "%$value%");
            });
        });
    }

    public function type($value)
    {
        $this->builder->where("type", $value);
    }

    public function role($value)
    {
        $this->builder->where("role", $value);
    }

    public function sort($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }


    public function branch($value)
    {
        $this->builder->where("branch_id", $value);
    }

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

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%')
                // ->orWhere('last_name', 'like', '%' . $value . '%')
            ;
        });
    }
}
