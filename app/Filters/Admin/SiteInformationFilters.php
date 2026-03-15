<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class SiteInformationFilters extends Filters
{

    protected $filters = ['status', 'from', 'to', 'search', 'sortBy', 'year'];

    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function year($value)
    {
        $this->builder->when(request()->filled('year'), function ($q) use ($value) {
            $q->whereYear('created_at', '>=', $value);
        });
    }

    public function from($value)
    {
        $this->builder->when(request()->filled('from'), function ($q) use ($value) {
            $q->whereDate('created_at', '>=', $value);
        });
    }
    public function to($value)
    {
        $this->builder->when(request()->filled('to'), function ($q) use ($value) {
            $q->whereDate('created_at', '<=', $value);
        });
    }

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('title', 'like', '%' . $value . '%');
        });
    }

    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }
}
