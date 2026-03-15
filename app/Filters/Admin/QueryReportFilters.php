<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class QueryReportFilters extends Filters
{

    protected $filters = ['from', 'to', 'search', 'type', 'sortBy', 'report', 'status', 'report_type'];

    public function report($value)
    {
        $this->builder->where('reason', '!=', '');
    }

    public function status($value)
    {
        $this->builder->where('status', $value);
    }

    public function report_type($value)
    {
        $this->builder->where('service_type', $value);
    }


    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function from($value)
    {
        $this->builder->whereDate('created_at', $value);
    }
    public function to($value)
    {
        $this->builder->whereDate('created_at', $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->whereIn('user_id', function ($q) use ($value) {
                $q->select('id')->from('users')
                    ->where('first_name', 'like', '%' . $value . '%')
                    ->orwhere('last_name', 'like', '%' . $value . '%')
                    ->orWhere('email', 'like', '%' . $value . '%');
            })
                ->orWhere('reportable_type', 'like', '%' . $value . '%');
        });
    }
    public function type($value)
    {
        list($reportableValue, $reportable_type) = explode('_', $value);

        $this->builder->where('reportable_type', $reportable_type)
            ->when($reportable_type == 'ad', function ($q) use ($reportableValue) {
                $q->whereIn('reportable_id', function ($q) use ($reportableValue) {
                    $q->select('id')->from('ads')->where('type', $reportableValue);
                });
            })->when($reportable_type == 'order', function ($q) use ($reportableValue) {
                $q->whereIn('reportable_id', function ($q) use ($reportableValue) {
                    $q->select('id')->from('orders')->where('type', $reportableValue);
                });
            });
    }
}
