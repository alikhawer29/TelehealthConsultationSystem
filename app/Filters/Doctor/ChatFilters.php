<?php

namespace App\Filters\Doctor;

use App\Core\Abstracts\Filters;

class ChatFilters extends Filters
{

    protected $filters = ['status', 'search', 'sortBy', 'owner', 'chat_id', 'type', 'groupBy', 'chat_type'];

    public function groupBy($value)
    {
        $this->builder->groupBy('payable_id');
    }

    public function chat_type($value)
    {
        $this->builder->where("type", $value);
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
    }


    public function type($value)
    {
        $this->builder->whereIn("chat_type", ['admin_doctor', 'user_doctor']);
    }

    public function chat_id($value)
    {
        $this->builder->where("id", $value);
    }

    public function year($value)
    {
        $this->builder->when(request()->filled('year'), function ($q) use ($value) {
            $q->whereYear('created_at', '>=', $value);
        });
    }

    public function fromDate($value)
    {
        $this->builder->when(request()->filled('fromDate'), function ($q) use ($value) {
            $q->whereDate('created_at', '>=', $value);
        });
    }
    public function toDate($value)
    {
        $this->builder->when(request()->filled('toDate'), function ($q) use ($value) {
            $q->whereDate('created_at', '<=', $value);
        });
    }

    public function search($value)
    {
        $this->builder->where(function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%')
                // ->orWhere('last_name', 'like', '%' . $value . '%')
                ->orWhere('email', 'like', '%' . $value . '%');
        });
    }

    public function sortBy($value)
    {
        $this->builder->orderBy('created_at', 'desc');
    }

    public function owner($value)
    {
        $id = request()->user()->id;
        $this->builder->where(function ($query) use ($id) {
            $query->where('sender_id', $id)
                ->orWhere('receiver_id', $id);
        });
    }
}
