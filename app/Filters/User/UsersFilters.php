<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class UsersFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sort', 'year', 'role', 'parent_id', 'branch', 'is_profile_completed'];

    public function parent_id($value)
    {
        $user = request()->user();
        $parentId = $user->role === 'user' ? $user->id : $user->parent_id;
        $this->builder->where('parent_id', $parentId);
    }



    public function is_profile_completed($value)
    {
        if ($value) {
            $this->builder->whereHas('license')
                ->whereHas('education');
        }
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
            $q->where('business_name', 'like', '%' . $value . '%')
                ->orWhere('user_name', 'like', '%' . $value . '%')
                ->orWhere('email', 'like', '%' . $value . '%')
                ->orWhere('user_id', 'like', '%' . $value . '%')
                ->orWhere('id', 'like', '%' . $value . '%');
        });
    }
}
