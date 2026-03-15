<?php

namespace App\Filters\Doctor;

use App\Core\Abstracts\Filters;
use Illuminate\Support\Facades\Log;

class PrescriptionFilters extends Filters
{
    protected $filters = ['doctor_id', 'patient_id', 'status', 'search', 'from', 'to', 'role', 'type', 'sort', 'patient', 'doctor_patients_only'];

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

    /**
     * Filter prescriptions to only show patients that the doctor has appointments with
     */
    public function doctor_patients_only($doctorId)
    {
        $this->builder->whereIn('patient_id', function ($q) use ($doctorId) {
            $q->select('user_id')
                ->from('appointments')
                ->where('bookable_type', 'App\Models\User')
                ->where('bookable_id', $doctorId)
                ->distinct();
        });
    }
}
