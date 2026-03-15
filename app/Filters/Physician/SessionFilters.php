<?php

namespace App\Filters\Doctor;

use App\Core\Abstracts\Filters;

class SessionFilters extends Filters
{

    protected $filters = [
        'search',
        'status',
        'gender',
        'sortBy',
        'appointment_type',
        'to_date',
        'from_date',
        'session_type',
        'appointment_status',
        'owner',
        'appointment_status',
        'payment_status'
    ];

    public function session_type($value)
    {

        $this->builder->where("session_type", $value);
    }

    public function appointment_status($value)
    {
        $this->builder->where("appointment_status", $value);
    }

    public function payment_status($value)
    {
        $this->builder->where("payment_status", $value);
    }


    public function owner($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("bookable_id", $id)->where('bookable_type', 'App\Models\User');
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
    }



    public function sortBy($value)
    {
        $this->builder->orderBy('id', 'desc');
    }
    public function appointment_type($value)
    {
        $this->builder->where("type", $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('booking_id', 'like', '%' . $value . '%');
            $q->orWhere('amount', 'like', '%' . $value . '%');
        });
    }
    public function to_date($value)
    {
        $this->builder->whereDate('appointment_date', '<=', $value);
    }
    public function from_date($value)
    {
        $this->builder->whereDate('appointment_date', '>=', $value);
    }
}
