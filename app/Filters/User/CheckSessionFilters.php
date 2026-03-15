<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class CheckSessionFilters extends Filters
{
    protected $filters = ['appointment_id', 'user_id', 'from', 'to', 'search'];

    public function appointment_id($value)
    {
        $this->builder->where('appointment_id', $value);
    }

    public function user_id($value)
    {
        $this->builder->where('user_id', $value);
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
            $q->whereHas('user', function ($userQuery) use ($value) {
                $userQuery->where('name', 'like', '%' . $value . '%')
                         ->orWhere('email', 'like', '%' . $value . '%');
            })->orWhereHas('appointment', function ($appointmentQuery) use ($value) {
                $appointmentQuery->where('id', 'like', '%' . $value . '%');
            });
        });
    }
}