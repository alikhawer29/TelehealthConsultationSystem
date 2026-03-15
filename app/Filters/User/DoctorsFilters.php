<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class DoctorsFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sort', 'year', 'role', 'speciality', 'branch', 'session_type'];

    // public function speciality($value)
    // {
    //     $this->builder->where(function ($query) use ($value) {
    //         $query->whereIn('id', function ($subQuery) use ($value) {
    //             $subQuery->select('user_id')
    //                 ->from('license')
    //                 ->where('specialty', 'like', "%$value%");
    //         });
    //     });
    // }

    public function speciality($values)
    {
        $this->builder->whereIn('id', function ($subQuery) use ($values) {
            $subQuery->select('user_id')
                ->from('license')
                ->where(function ($q) use ($values) {
                    foreach ($values as $value) {
                        $q->orWhere('specialty', 'like', "%$value%");
                    }
                });
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
        // Hash the search value using the same method used for storing
        $hashedValue = hash('sha256', strtolower(trim($value))); // or whatever hash algorithm you're using

        $this->builder->where(function ($q) use ($hashedValue) {
            $q->where('first_name_hash', 'like', '%' . $hashedValue . '%')
                ->orWhere('last_name_hash', 'like', '%' . $hashedValue . '%');
        });
    }
}
