<?php

namespace App\Filters\Physician;

use App\Core\Abstracts\Filters;

class AppointmentFilters extends Filters
{

    protected $filters = [
        'search',
        'status',
        'gender',
        'sortBy',
        'appointment_type',
        'to',
        'from',
        'service_type',
        'appointment_status',
        'owner',
        'appointment_status',
        'payment_status',
        'appointment_to_date',
        'appointment_from_date'
    ];

    public function service_type($value)
    {

        $this->builder->where("service_type", $value);
    }

    public function payment_status($value)
    {
        $this->builder->where("payment_status", $value);
    }


    public function owner($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("provider", $id)->where('bookable_type', '!=', 'App\Models\User');
    }

    public function status($value)
    {
        // $this->builder->where("status", $value);
        $this->builder->where('status', '!=', 'pending');

        if ($value === 'scheduled') {
            $this->builder->whereIn('status', ['scheduled', 'requested']);
        } else {
            $this->builder->where('status', $value);
        }
    }


    public function appointment_status($value)
    {
        $this->builder->where("appointment_status", $value);
    }
    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
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
            $q->orWhereIn('user_id', function ($subQuery) use ($value) {
                $subQuery->select('id')
                    ->from('users')
                    ->where('first_name', 'like', "%$value%");
            });
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

    public function appointment_to_date($value)
    {
        $this->builder->whereDate('appointment_date', '<=', $value);
    }
    public function appointment_from_date($value)
    {
        $this->builder->whereDate('appointment_date', '>=', $value);
    }
}
