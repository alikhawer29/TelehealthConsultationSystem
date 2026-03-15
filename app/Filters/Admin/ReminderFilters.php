<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class ReminderFilters extends Filters
{

    protected $filters = ['owner', 'order', 'slot_type', 'type', 'user_type'];

    public function user_type($value)
    {
        $this->builder->where("user_type", $value);
    }


    public function owner($value)
    {
        $user = request()->user();
        $id = $user->id;
        $this->builder->where("reference_id", $id);
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
        $this->builder->where("business_name", 'like', "%{$value}%");
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
