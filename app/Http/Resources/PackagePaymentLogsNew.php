<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackagePaymentLogsNew extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'package_id' => $this->package->id,
            'package_name' => $this->package->name,
            'charge' => '$' . $this->package->cost,
            'purchase_on' => $this->created_at->format('Y-m-d'),
            'expiry_date' => $this->expire_date,
            'status' => $this->expire_date <= now()->format('Y-m-d') ? 'Expired' : 'Active',
        ];
    }
}
