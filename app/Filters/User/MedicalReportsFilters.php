<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class MedicalReportsFilters extends Filters
{

    protected $filters = [
        'search',
        'status',
        'type',
        'sortBy',
        'to',
        'from',
        'owner'
    ];

    public function sortBy($value)
    {

        $this->builder->orderBy("created_at", $value);
    }


    public function type($value)
    {

        $this->builder->where("type", $value);
    }

    public function owner($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("user_id", $id);
    }


    public function status($value)
    {
        $this->builder->where('status', $value);
    }


    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('file_name', 'like', '%' . $value . '%');
        });
    }


    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }
    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }
}
