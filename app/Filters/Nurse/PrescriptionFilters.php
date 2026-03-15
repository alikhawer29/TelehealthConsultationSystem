<?php

namespace App\Filters\Nurse;

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
        $searchTerm = strtolower(trim($value));

        // Since names are encrypted, we need to get all users and filter in PHP
        // This is not ideal for performance but necessary for encrypted data
        $userIds = \App\Models\User::where('role', 'user')
            ->where('status', 1)
            ->get()
            ->filter(function ($user) use ($searchTerm) {
                $firstName = strtolower($user->first_name ?? '');
                $lastName = strtolower($user->last_name ?? '');
                $fullName = trim($firstName . ' ' . $lastName);

                return str_contains($firstName, $searchTerm) ||
                    str_contains($lastName, $searchTerm) ||
                    str_contains($fullName, $searchTerm);
            })
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            // If no users match, return no results
            $this->builder->where('patient_id', -1);
        } else {
            $this->builder->whereIn('patient_id', $userIds);
        }
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
