<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceResource extends JsonResource
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
            'name' => $this->name,
            'card_number' => $this->card_number,
            'status' => $this->status,
            'reason' => $this->reason,
            'card_holder_name' => $this->card_holder_name,
            'status_detail' => $this->status_detail,
            'is_insured' => (bool) $this->is_insured,
            'file' => new FileResource($this->whenLoaded('file')),
        ];
    }
}
