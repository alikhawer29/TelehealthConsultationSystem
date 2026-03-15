<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FavouriteResource extends JsonResource
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
            "id" => $this->id,
            "supplier_id" => $this->supplier_id,
            "supplier_name" => $this->supplier->first_name . ' ' . $this->supplier->last_name,
            "terms" => $this->terms,
            "rating" => $this->rating,
        ];
    }
}
