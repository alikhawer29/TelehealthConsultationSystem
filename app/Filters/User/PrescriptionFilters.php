<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class PrescriptionFilters extends Filters
{
    protected $filters = ['doctor_id', 'patient_id', 'status', 'search', 'from', 'to', 'role', 'type', 'sort'];

    public function sort($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function doctor_id($value)
    {
        $this->builder->where('doctor_id', $value);
    }

    public function patient_id($value)
    {
        $this->builder->where('patient_id', $value);
    }

    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function type($value)
    {
        $this->builder->where('type', $value);
    }

    public function role($value)
    {
        $this->builder->where('role', $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->where('medication', 'like', '%' . $value . '%')
                ->orWhere('dosage', 'like', '%' . $value . '%')
                ->orWhere('role', 'like', '%' . $value . '%')
                ->orWhere('type', 'like', '%' . $value . '%');
        });
    }

    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }
}
