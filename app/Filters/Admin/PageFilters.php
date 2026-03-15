<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class PageFilters extends Filters
{
    protected $filters = ['status', 'from', 'to', 'search', 'sort'];

    public function sort($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%')
              ->orWhere('title', 'like', '%' . $value . '%')
              ->orWhere('slug', 'like', '%' . $value . '%');
        });
    }
}