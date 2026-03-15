<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class AppointmentFilters extends Filters
{

    protected $filters = [
        'search',
        'status',
        'gender',
        'sortBy',
        'appointment_type',
        'to_date',
        'from_date',
        'service_type',
        'appointment_status',
        'owner',
        'appointment_status',
        'payment_status',
        'not_pending'
    ];

    public function service_type($value)
    {        
        if ($value === 'all') {     
            $this->builder->where('service_type', '!=', 'doctor');
            return;
        }

        if ($value === 'lab') {
            $this->builder->where(function ($query) {
                $query->where('service_type', 'lab');
                $query->orwhere('service_type', 'lab_bundle');
                $query->orwhere('service_type', 'lab_custom');
            });
            return;
        }

        if ($value === 'iv_drip') {
            $this->builder->where(function ($query) {
                $query->where('service_type', 'iv_drip');
                $query->orwhere('service_type', 'iv_drip_custom');
            });
            return;
        }

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
        $this->builder->where("user_id", $id);
    }


    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function not_pending($value)
    {
        $this->builder->where('status', '!=', 'pending');
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

        $this->builder->where(function ($query) use ($value, $serviceType) {
            $query->where('booking_id', 'like', "%$value%")
                ->orWhere('amount', 'like', "%$value%");

            if ($serviceType === 'doctor') {
                $query->orWhereIn('bookable_id', function ($subQuery) use ($value) {
                    $subQuery->select('id')
                        ->from('users')
                        ->where('first_name', 'like', "%$value%");
                })->where('bookable_type', 'App\Models\User');
            } else {
                // For service
                $query->orWhere(function ($q) use ($value) {
                    $q->where('bookable_type', 'App\Models\Service')
                        ->whereIn('bookable_id', function ($subQuery) use ($value) {
                            $subQuery->select('id')
                                ->from('service')
                                ->where('name', 'like', "%$value%");
                        });
                });

                // For service bundle
                $query->orWhere(function ($q) use ($value) {
                    $q->where('bookable_type', 'App\Models\ServiceBundle')
                        ->whereIn('bookable_id', function ($subQuery) use ($value) {
                            $subQuery->select('id')
                                ->from('service_bundles')
                                ->where('bundle_name', 'like', "%$value%");
                        });
                });
            }
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
