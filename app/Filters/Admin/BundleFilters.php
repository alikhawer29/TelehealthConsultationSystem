<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class BundleFilters extends Filters
{

    protected $filters = ['owner', 'order', 'slot_type', 'type', 'search', 'from', 'to', 'status'];

    public function from($value)
    {
        $this->builder->whereDate('created_at', $value);
    }
    public function to($value)
    {
        $this->builder->whereDate('created_at', $value);
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
        $slotType = $value === 'homecare' ? 'homecare_service' : 'lab_service';
        $this->builder->where("slot_type", $slotType);
    }

    public function type($value)
    {
        $this->builder->where("type", $value);
    }

    public function status($value)
    {
        $this->builder->where("status", $value);
    }

    public function search($value)
    {
        $this->builder->where("bundle_name", 'like', "%{$value}%");
    }
    public function order($value)
    {
        $this->builder->orderBy('created_at', 'desc');
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
