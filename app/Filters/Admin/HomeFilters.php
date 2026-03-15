<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;
use App\Models\Commissions;
use App\Models\Vendor;

class HomeFilters extends Filters
{

    protected $filters = ['appointment_status', 'status', 'doctor', 'nurse', 'physician', 'role', 'buyer', 'my_orders', 'my_users2', 'my_users', 'user', 'branch', 'order', 'payment', 'payments', 'commission', 'personal', 'personal_payment', 'order_type', 'player', 'coach', 'psychologist', 'appointment'];


    public function role($value)
    {
        $this->builder->where('role', $value);
    }

    public function status($value)
    {
        if (!request('role')) {
            $this->builder->whereIn('status', ['scheduled', 'requested']);
        }
    }

    public function appointment_status($value)
    {
        if (!request('role')) {
            $this->builder->where('appointment_status', 'completed');
        }
    }

    public function personal($value)
    {
        $this->builder->where('order_owner_id', $value)->where('order_owner_type', Vendor::class);
    }

    public function order_type($value)
    {
        $this->builder->where('order_type', $value);
    }

    public function orders($value)
    {
        $this->builder->where('order_type', $value);
    }

    public function branch($value)
    {
        $this->builder->where('restaurant_id', request()->user()->id);
    }

    public function personal_payment($value)
    {
        $this->builder->whereIn('payable_id', function ($q) use ($value) {
            $q->select('id')
                ->from('orders')
                ->where('order_owner_type', Vendor::class)
                ->where('order_owner_id', $value->id);
        });
    }

    public function my_users($value)
    {
        $this->builder->whereIn('id', function ($q) use ($value) {
            $q->select('user_id')
                ->from('orders')
                ->where('branch_id', request()->user()->id);
        });
    }
    public function my_orders($value)
    {
        $this->builder->where('branch_id', request()->user()->id);
    }

    public function my_users2($value)
    {
        $this->builder->where('branch_id', request()->user()->id)->whereNotIn('order_status', ['rejected', 'pending']);
    }




    public function buyer($value)
    {
        // $chartType = request('chart_type', 'yearly');

        // if ($chartType === 'yearly') {

        $this->builder
            ->select([
                \DB::raw('MONTHNAME(created_at) as month'),
                \DB::raw('COUNT(*) as total'),

            ])
            // ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
            ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')")
            ->groupBy('month');
        // } elseif ($chartType === 'monthly') {
        //     $this->builder
        //         ->select([
        //             \DB::raw('DAY(created_at) as day'),
        //             \DB::raw('COUNT(*) as total'),
        //         ])
        //         ->whereRaw('YEAR(created_at) = ?', [now()->year])
        //         ->whereRaw('MONTH(created_at) = ?', [now()->month])
        //         ->whereRaw('created_at >= ?', [now()->subDays(29)->startOfDay()->toTimeString()])
        //         ->groupBy('day');
        // }
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


    public function user($value)
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

    public function doctor($value)
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

    public function nurse($value)
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

    public function physician($value)
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

    public function payment($value)
    {
        $chartType = request('type', 'weekly');
        switch ($chartType) {
            case 'yearly':
                $this->builder
                    ->select([
                        \DB::raw('MONTHNAME(created_at) as month'),
                        \DB::raw('SUM(amount) as total'),
                    ])
                    ->where('status', 'paid')
                    ->whereRaw('YEAR(created_at) = ?', ['year' => now()->year])
                    ->orderByRaw("FIELD(MONTH, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')")
                    ->groupBy('month');
                break;

            case 'monthly':
                $this->builder
                    ->select([
                        \DB::raw('DAY(created_at) as day'),
                        \DB::raw('SUM(amount) as total'),
                    ])
                    ->where('status', 'paid')
                    ->whereRaw('YEAR(created_at) = ?', [now()->year])
                    ->whereRaw('MONTH(created_at) = ?', [now()->month])
                    ->whereBetween('created_at', [
                        now()->startOfMonth()->toTimeString(),  // Start of current month
                        now()->endOfMonth()->toTimeString()     // End of current month
                    ])
                    ->groupBy('day');
                break;

            case 'past6months':
                $this->builder
                    ->select([
                        \DB::raw('MONTHNAME(created_at) as month'),
                        \DB::raw('SUM(amount) as total'),
                    ])
                    ->where('status', 'paid')
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
                        \DB::raw('SUM(amount) as total'),
                    ])
                    ->where('status', 'paid')
                    ->whereBetween('created_at', [now()->subDays(6)->format('Y-m-d'), now()->format('Y-m-d')])
                    ->groupBy(\DB::raw('DATE(created_at)'))
                    ->orderBy('date', 'asc');
                break;
        }
    }



    public function order($value)
    {
        $chartType = request('chart_type', 'yearly');

        if ($chartType === 'yearly') {

            $this->builder
                ->select([
                    \DB::raw('MONTHNAME(created_at) as month'),
                    \DB::raw('COUNT(*) as total'),

                ])
                // ->whereRaw('MONTH(created_at) = MONTH(NOW())')
                ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
                ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')")
                ->groupBy('month');
        } elseif ($chartType === 'monthly') {
            $this->builder
                ->select([
                    \DB::raw('DAY(created_at) as day'),
                    \DB::raw('COUNT(*) as total'),
                ])
                ->whereRaw('YEAR(created_at) = ?', [now()->year])
                ->whereRaw('MONTH(created_at) = ?', [now()->month])
                ->whereRaw('created_at >= ?', [now()->subDays(29)->startOfDay()->toTimeString()])
                ->groupBy('day');
        }
    }





    public function commission()
    {
        $this->builder
            ->withCount([
                'commission as commission' => fn($q) => $q->select(\DB::raw('rate / 100')),
                'products as total' => fn($q) => $q->select(\DB::raw('SUM(price * qty)')),
            ])
            ->addSelect([
                \DB::raw('MONTHNAME(created_at) as month'),
                \DB::raw('(SELECT SUM(total * commission)) as total'),

            ])
            ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
            ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')");
        // ->groupBy('month');
    }
}
