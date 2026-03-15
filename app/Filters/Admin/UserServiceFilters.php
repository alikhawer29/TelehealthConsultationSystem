<?php

namespace App\Filters\Admin;

use App\Models\User;
use App\Core\Abstracts\Filters;

class UserServiceFilters extends Filters
{

    protected $filters = [
        'status',
        'from',
        'to',
        'search',
        'sortBy',
        'year',
        'owner',
        'service_type',
        'bookable_id',
        'appointment_from',
        'appointment_to',
        'appointment_status',
        'session_type',
        'booking_type',
        'user_id'
    ];

    public function appointment_status($value)
    {
        if (in_array($value, ['upcoming', 'inprogress', 'completed'])) {
            $this->builder->where('appointment_status', $value)
                ->where('status', '!=', 'cancelled');
        } elseif ($value === 'cancelled') {
            $this->builder->where('status', 'cancelled');
        }
    }

    public function booking_type($value)
    {
        $this->builder->where('service_type', $value);
    }


    public function session_type($value)
    {
        $this->builder->where('session_type', $value);
    }


    public function bookable_id($value)
    {
        if (request('service_type') === 'doctor') {
            $this->builder->where('bookable_id', $value);
        } else {
            $this->builder->where('provider', $value);
        }
    }

    public function user_id($value)
    {
        $this->builder->where('user_id', $value);
    }

    public function status($value)
    {
        $this->builder->whereIn("status", ['scheduled', 'cancelled', 'requested']);
    }

    public function service_type($value)
    {
        if ($value === 'lab') {
            $this->builder->whereIn("service_type", ['lab', 'lab_custom', 'lab_bundle']);
        } else {
            $this->builder->where("service_type", $value);
        }
    }


    public function year($value)
    {
        $this->builder->when(request()->filled('year'), function ($q) use ($value) {
            $q->whereYear('created_at', '>=', $value);
        });
    }

    public function appointment_from($value)
    {
        $this->builder->whereDate('appointment_date', '>=', $value);
    }

    public function appointment_to($value)
    {
        $this->builder->whereDate('appointment_date', '<=', $value);
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
        $serviceType = request('service_type');


        $this->builder->where(function ($query) use ($value, $serviceType) {
            $query->where('booking_id', 'like', '%' . $value . '%')
                ->orWhere('amount', 'like', '%' . $value . '%')
                ->orWhereIn('user_id', function ($subQuery) use ($value, $serviceType) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%")
                        ->orWhere('last_name', 'like', "%$value%");
                });
            if ($serviceType == 'doctor') {
                $query->orWhereIn('bookable_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $query->orWhereIn('user_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
            }
            if ($serviceType == 'homecare') {
                $query->orWhereIn('provider', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $query->orWhereIn('user_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $query->orWhereIn('bookable_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('service')
                        ->where('name', 'like', "%$value%");
                });
            }
            if ($serviceType == 'lab') {
                $query->orWhereIn('provider', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $query->orWhereIn('user_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
            }
        });
    }

    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function owner($value)
    {
        $this->builder->where("user_id", $value);
    }
}
