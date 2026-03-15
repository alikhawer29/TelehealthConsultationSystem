<?php

namespace App\Filters\Doctor;

use App\Core\Abstracts\Filters;

class SlotsFilters extends Filters
{

    protected $filters = ['owner', 'order', 'slot_type', 'search', 'groupBy', 'from', 'to', 'reference_id'];

    public function reference_id($value)
    {
        $this->builder->where('reference_id',  $value);
    }

    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }


    public function groupBy($value)
    {
        $this->builder->groupBy('created_at');
    }

    public function owner($value)
    {
        $user = request()->user();
        $type = get_class(request()->user());
        $id = $user->id;
        $this->builder->where("slotable_type", $type);
        $this->builder->where("slotable_id", $id);
    }

    public function slot_type($value)
    {
        $this->builder->where("slot_type", $value);
    }


    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->whereIn('reference_id', function ($subQuery) use ($value) {
                $subQuery->select('id')
                    ->from('service')
                    ->where('name', 'like', "%$value%");
            });
        });
    }

    public function order($value)
    {
        $this->builder->orderBy('created_at', 'DESC');
    }

    public function location($value)
    {
        $this->builder
            ->addSelect([
                getDistanceQuery($value['lat'], $value['lng']),
            ]);
    }

    public function distance($value)
    {
        if ($this->request->filled('location')) {
            $this->builder->having('distance', '<=', $value);
        }
    }
}
