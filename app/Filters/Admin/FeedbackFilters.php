<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class FeedbackFilters extends Filters
{

    protected $filters = ['from', 'to', 'search', 'type', 'sortBy', 'status'];

    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
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
        $this->builder->where('name', 'like', '%' . $value . '%')
            ->orwhere('email', 'like', '%' . $value . '%');
    }
    public function type($value)
    {
        $this->builder->where('support_type', $value);
    }
    public function status($value)
    {
        if ($value == 1) {
            $this->builder->where('admin_comments', '!=', null);
        } else {
            $this->builder->where('admin_comments', null);
        }
    }
}
