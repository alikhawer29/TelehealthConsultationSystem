<?php

namespace App\Filters\Admin;

use App\Core\Abstracts\Filters;

class SlotsFilters extends Filters
{

    protected $filters = ['owner', 'order', 'slot_type', 'search', 'groupByRef', 'groupByDate', 'from', 'to', 'reference_id'];

    // public function slot_type($value)
    // {
    //     $typeMap = [
    //         'homecare'     => 'homecare_service',
    //         'lab'          => 'lab_service',
    //         'iv_drip'      => 'iv_drip',
    //         'nursing_care' => 'nursing_care',
    //     ];

    //     $slotType = $typeMap[$value] ?? '';

    //     if ($slotType !== '') {
    //         $this->builder->where("slot_type", $slotType);
    //     }
    // }

    public function slot_type($value)
    {
        $typeMap = [
            'homecare'     => 'homecare_service',
            'lab'          => 'lab_service',
            'iv_drip'      => 'iv_drip',
            'nursing_care' => 'nursing_care',
        ];

        // If multiple values provided (e.g., ['homecare', 'nursing_care'])
        if (is_array($value)) {
            $mapped = array_filter(array_map(fn($v) => $typeMap[$v] ?? null, $value));
            if (!empty($mapped)) {
                $this->builder->whereIn("slot_type", $mapped);
            }
        } else {
            // Single value handling
            $slotType = $typeMap[$value] ?? null;
            if ($slotType) {
                $this->builder->where("slot_type", $slotType);
            }
        }
    }


    public function from($value)
    {
        $this->builder->whereDate('created_at', '>=', $value);
    }

    public function to($value)
    {
        $this->builder->whereDate('created_at', '<=', $value);
    }

    public function reference_id($value)
    {
        $this->builder->where('reference_id', $value);
    }



    public function groupByRef($value)
    {
        $this->builder->groupBy('reference_id');
    }

    public function groupByDate($value)
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
