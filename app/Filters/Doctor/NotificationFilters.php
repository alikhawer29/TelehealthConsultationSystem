<?php

namespace App\Filters\Doctor;

use App\Core\Abstracts\Filters;

class NotificationFilters extends Filters
{

    protected $filters = ['personal', 'unread_only', 'order', 'status'];

    public function personal($value)
    {
        $type = get_class($value);
        $id = $value->id;
        $this->builder->where("notifiable_type", $type)->where('notifiable_id', $id);
    }

    public function order($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function unread_only()
    {
        $this->builder->whereNull('read_at');
    }
    public function status($value)
    {
        if ($value == 'read') {
            $this->builder->whereNotNull('read_at');
        } else {
            $this->builder->whereNull('read_at');
        }
    }
}
