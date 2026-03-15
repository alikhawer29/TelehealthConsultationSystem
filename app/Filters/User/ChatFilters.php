<?php

namespace App\Filters\User;

use App\Core\Abstracts\Filters;

class ChatFilters extends Filters
{

    protected $filters = ['status', 'search', 'sortBy', 'owner', 'chat_id', 'type', 'groupBy', 'chat_type', 'from', 'to', 'message_type'];

    public function groupBy($value)
    {
        $this->builder->groupBy('payable_id');
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function type($value)
    {
        $this->builder->where("chat_type", $value);
    }

    public function message_type($value)
    {
        $this->builder->where("type", $value);
    }

    public function chat_type($value)
    {
        $this->builder->where("type", $value);
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

        $this->builder->where(function ($query) use ($value) {
            $query->whereIn('id', function ($subQuery) use ($value) {
                $subQuery->select('chat_id')
                    ->from('messages')
                    ->whereIn('sender_id', function ($subQuery) use ($value) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('first_name', 'like', "%$value%")
                            ->orwhere('last_name', 'like', "%$value%");
                    })
                    ->orwhereIn('receiver_id', function ($subQuery) use ($value) {
                        $subQuery->select('id')
                            ->from('users')
                            ->where('first_name', 'like', "%$value%")
                            ->orwhere('last_name', 'like', "%$value%");
                    });
            });
        });
    }

    public function sortBy($value)
    {
        // $this->builder->orderBy('created_at', 'desc');

        $this->builder->orderByDesc(function ($query) {
            $query->select('created_at')
                ->from('messages')
                ->whereColumn('messages.chat_id', 'chats.id')
                ->latest()
                ->limit(1);
        });
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
