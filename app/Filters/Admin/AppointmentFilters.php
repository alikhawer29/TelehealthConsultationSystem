<?php

namespace App\Filters\Admin;

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
        'session_type',
        'appointment_to',
        'appointment_from',
        'homecare_status',
        'booking_type',
        'child_service_type'
    ];

    public function service_type($value)
    {
        // If main service_type is 'doctor', apply directly
        if ($value === 'doctor') {
            $this->builder->where("service_type", $value);
        }

        // If 'wella', we defer filtering to child_service_type
        if ($value === 'wella') {
            // Don't apply filter here, let child_service_type handle it
        }
    }

    public function child_service_type($value)
    {
        if (request('service_type') === 'wella') {
            $this->builder->where(function ($query) use ($value) {
                if ($value === 'lab') {
                    $query->whereIn('service_type', ['lab', 'lab_bundle', 'lab_custom']);
                } elseif ($value === 'iv_drip') {
                    $query->whereIn('service_type', ['iv_drip', 'iv_drip_custom']);
                } else {
                    $query->where('service_type', $value);
                }
            });
        }
    }

    public function booking_type($value)
    {
        $this->builder->where("service_type", $value);
    }

    public function homecare_status($value)
    {
        $this->builder->where("status", $value);
    }

    public function session_type($value)
    {
        $this->builder->where("session_type", $value);
    }


    public function payment_status($value)
    {
        $this->builder->where("payment_status", $value);
    }


    public function owner($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("user_id", $id);
    }

    public function status($value)
    {

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
        $this->builder->orderBy('id', 'desc');
    }
    public function appointment_type($value)
    {
        $this->builder->where("type", $value);
    }

    public function search($value)
    {
        $serviceType = request('service_type');

        $this->builder->where(function ($q) use ($value, $serviceType) {
            $q->where('booking_id', 'like', '%' . $value . '%');
            $q->orWhere('amount', 'like', '%' . $value . '%');

            if ($serviceType == 'doctor') {
                $q->orWhereIn('bookable_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $q->orWhereIn('user_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
            } else {
                $q->orWhereIn('provider', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
                $q->orWhereIn('user_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                });
            }
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

    public function appointment_to($value)
    {
        $this->builder->whereDate('appointment_date', '<=', $value);
    }
    public function appointment_from($value)
    {
        $this->builder->whereDate('appointment_date', '>=', $value);
    }
}
