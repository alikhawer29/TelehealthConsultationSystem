<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class MedicalOrderFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sortBy', 'year', 'personal'];

    public function personal($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("user_id", $id);
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
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
        $this->builder->where(function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%')
                ->orWhere('amount', 'like', '%' . $value . '%');
        });
    }

    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }
}
