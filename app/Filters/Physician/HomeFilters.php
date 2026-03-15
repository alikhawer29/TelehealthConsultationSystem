<?php

namespace App\Filters\Physician;

use App\Core\Abstracts\Filters;
use App\Models\User;

class HomeFilters extends Filters
{

    protected $filters = [
        'service_type',
        'role',
        'personal',
        'appointment',
        'status',
        'appointment_status',
        'chart_status'
    ];

    public function chart_status($value)
    {
        $appointmentStatus = request('appointment_status');
        if ($appointmentStatus === 'completed') {
            $this->builder->whereIn('status', ['scheduled', 'requested']);
        } else {
            $this->builder->whereIn('status', ['scheduled', 'requested']);
        }
    }

    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function appointment_status($value)
    {
        $this->builder->where('appointment_status', $value);
    }

    public function service_type($value)
    {
        $this->builder->where('service_type', 'homecare');
    }

    public function role($value)
    {
        $this->builder->where('role', $value);
    }

    public function personal($value)
    {
        $this->builder->where('provider', $value)->where('bookable_type', '!=', User::class);
    }

    public function appointment($value)
    {
        $chartType = request('type', 'yearly');
        switch ($chartType) {
            case 'yearly':
                $this->builder
                    ->select([
                        \DB::raw('MONTHNAME(created_at) as month'),
                        \DB::raw('COUNT(*) as total'),
                    ])
                    ->whereRaw('YEAR(created_at) = ?', ['year' => now()->year])
                    ->orderByRaw("FIELD(MONTH, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')")
                    ->groupBy('month');
                break;

            case 'monthly':
                $this->builder
                    ->select([
                        \DB::raw('DAY(created_at) as day'),
                        \DB::raw('COUNT(*) as total'),
                    ])
                    ->whereRaw('YEAR(created_at) = ?', [now()->year])
                    ->whereRaw('MONTH(created_at) = ?', [now()->month])
                    ->whereBetween('created_at', [
                        now()->startOfMonth()->toDateTimeString(),  // Correct: Full datetime string
                        now()->endOfMonth()->toDateTimeString()     // Correct: Full datetime string
                    ])
                    ->groupBy('day');
                break;

            case 'past6months':
                $this->builder
                    ->select([
                        \DB::raw('MONTHNAME(created_at) as month'),
                        \DB::raw('COUNT(*) as total'),
                    ])
                    ->whereBetween('created_at', [
                        now()->subMonths(6)->startOfMonth()->format('Y-m-d'),
                        now()->endOfMonth()->format('Y-m-d')
                    ])
                    ->groupBy('month')
                    ->orderByRaw('DATE_FORMAT(created_at, "%Y-%m") DESC');
                break;

            case 'weekly':
            default:
                $this->builder
                    ->select([
                        \DB::raw('DATE(created_at) as date'),
                        \DB::raw('COUNT(*) as total'),
                    ])
                    ->whereBetween('created_at', [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')])
                    ->groupBy(\DB::raw('DATE(created_at)'))
                    ->orderBy('date', 'asc');
                break;
        }
    }
}
