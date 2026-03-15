<?php

namespace App\Filters\Shelter;

use App\Core\Abstracts\Filters;
use App\Models\Commissions;

class HomeFilters extends Filters
{

    protected $filters = ['order', 'payment', 'commission', 'owner_id', 'order_owner_id'];


    public function owner_id($value)
    {
        $this->builder->where('owner_id', $value);
        // ->where('order_owner_type', 'App\\Models\\Shelter');
    }

    public function order_owner_id($value)
    {
        $this->builder->where('order_owner_id', $value);
        // ->where('order_owner_type', 'App\\Models\\Shelter');
    }

    public function order($value)
    {
        $this->builder
            ->select([
                \DB::raw('MONTHNAME(created_at) as month'),
                \DB::raw('COUNT(*) as total'),

            ])
            // ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
            ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')")
            ->groupBy('month');
    }

    public function payment($value)
    {
        $this->builder
            ->select([
                \DB::raw('MONTHNAME(created_at) as month'),
                \DB::raw('SUM(amount) as total'),
            ])
            // ->whereRaw('MONTH(created_at) = MONTH(NOW())')
            ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
            ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')")
            ->groupBy('month');
    }


    public function commission()
    {
        $this->builder
            ->withCount([
                'commission as commission' => fn ($q) => $q->select(\DB::raw('rate / 100')),
                'products as total' => fn ($q) => $q->select(\DB::raw('SUM(price * qty)')),
            ])
            ->addSelect([
                \DB::raw('MONTHNAME(created_at) as month'),
                \DB::raw('(SELECT SUM(total - (total/100*(commission*100)))) as total'),

            ])
            ->whereRaw('YEAR(created_at) = ?', ['year' => request('year', date('Y'))])
            ->orderByRaw("FIELD(MONTH,'January','February','March','April','May','June','July','August','September','November','December')");
        // ->groupBy('month');

    }
}
