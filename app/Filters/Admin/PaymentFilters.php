<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;
use App\Models\Appointment;
use App\Models\Order;

class PaymentFilters extends Filters
{

    protected $filters = [
        'search',
        'personal',
        'from',
        'to',
        'order',
        'type',
        'groupBy',
        'booking_from',
        'booking_to',
        'payable_type',
        'status',
        'payer_type',
        'booking_type'

    ];

    // Store booking date values temporarily
    protected $bookingFrom;
    protected $bookingTo;


    public function booking_from($value)
    {
        $this->bookingFrom = $value;
        $this->applyBookingDateFilter();
    }

    public function booking_to($value)
    {
        $this->bookingTo = $value;
        $this->applyBookingDateFilter();
    }

    protected function applyBookingDateFilter()
    {
        // Only apply if at least one is present
        if (!$this->bookingFrom && !$this->bookingTo) {
            return;
        }

        $this->builder
            ->where('payable_type', Appointment::class)
            ->whereIn('payable_id', function ($q) {
                $q->select('id')->from('appointments');

                if ($this->bookingFrom) {
                    $q->whereDate('created_at', '>=', $this->bookingFrom);
                }

                if ($this->bookingTo) {
                    $q->whereDate('created_at', '<=', $this->bookingTo);
                }
            });
    }


    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function order($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function groupBy($value)
    {
        $this->builder->groupBy('payable_id');
    }
    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }
    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }



    public function payer_type($value)
    {

        $this->builder->whereHas('payer', function ($query) use ($value) {
            $query->where('role', $value);
        });
    }

    public function payable_type($value)
    {

        $this->builder->where('payable_type', $value);
    }
    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            // Search in payer_id (users table)
            $query->whereIn('payer_id', function ($q) use ($value) {
                $q->select('id')
                    ->from('users')
                    ->where('first_name', 'like', '%' . $value . '%')
                    ->orWhere('email', 'like', '%' . $value . '%')
                    ->orWhere('last_name', 'like', '%' . $value . '%');
            })

                // Search in payable_id where the provider's name matches in users table
                ->orWhereIn('payable_id', function ($q) use ($value) {
                    $q->select('id')
                        ->from('appointments')
                        ->whereIn('provider', function ($subQuery) use ($value) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('first_name', 'like', '%' . $value . '%')
                                ->orWhere('last_name', 'like', '%' . $value . '%');
                        });
                })
                // Search in bookable where the doctor's name matches in users table
                ->orWhereIn('payable_id', function ($q) use ($value) {
                    $q->select('id')
                        ->from('appointments')
                        ->whereIn('bookable_id', function ($subQuery) use ($value) {
                            $subQuery->select('id')
                                ->from('users')
                                ->where('first_name', 'like', '%' . $value . '%')
                                ->orWhere('last_name', 'like', '%' . $value . '%');
                        });
                });
        });
    }



    public function personal($value)
    {

        $type = get_class($value);
        $id = $value->id;
        $this->builder
            ->whereIn('payable_id', function ($q) use ($type, $id) {
                $q->select('id')
                    ->from('orders')
                    ->where('order_owner_type', $type)
                    ->where('order_owner_id', $id);
            });
    }

    public function type($value)
    {
        $serviceTypeMap = [
            'doctor'       => ['doctor'],
            'homecare'     => ['homecare'],
            'iv_drip'      => ['iv_drip', 'iv_drip_custom'],
            'nursing_care' => ['nursing_care'],
            'lab'          => ['lab', 'lab_custom', 'lab_bundle'],
        ];

        $types = $serviceTypeMap[$value] ?? ['lab', 'lab_custom', 'lab_bundle'];

        $this->builder
            ->where('payable_type', Appointment::class)
            ->whereIn('payable_id', function ($q) use ($types) {
                $q->select('id')->from('appointments')->whereIn('service_type', $types);
            });
    }


    public function booking_type($value)
    {
        $this->builder
            ->where('payable_type', Appointment::class)
            ->whereIn('payable_id', function ($q) use ($value) {
                $q->select('id')->from('appointments')->where('service_type', $value);
            });
    }
}
