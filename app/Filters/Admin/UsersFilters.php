<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class UsersFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sortBy', 'year', 'role', 'notUser', 'profession'];

    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function profession($value)
    {
        $this->builder->where("professional", $value);
    }

    public function notUser($value)
    {
        $this->builder->where("role", '!=', $value);
    }

    public function role($value)
    {
        $this->builder->where("role", $value);
    }

    public function year($value)
    {
        $this->builder->when(request()->filled('year'), function ($q) use ($value) {
            $q->whereYear('created_at', '>=', $value);
        });
    }

    public function from($value)
    {
        $this->builder->when(request()->filled('from'), function ($q) use ($value) {
            $q->whereDate('created_at', '>=', $value);
        });
    }
    public function to($value)
    {
        $this->builder->when(request()->filled('to'), function ($q) use ($value) {
            $q->whereDate('created_at', '<=', $value);
        });
    }


    public function search($value)
    {
        $hash = hash('sha256', strtolower(trim($value)));

        $this->builder->where(function ($q) use ($hash) {
            $q->where('first_name_hash', $hash)
                ->orWhere('last_name_hash', $hash)
                ->orWhere('email_hash', $hash);
        });
    }


    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }
}
