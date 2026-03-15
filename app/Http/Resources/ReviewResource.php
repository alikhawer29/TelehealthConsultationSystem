<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'id'         => $this->id,
            'rating'     => $this->rating,
            'review'     => $this->review,
            'created_at' => $this->created_at,
            'reviewer'   => [
                'id'         => $this->reviewer?->id,
                'first_name' => $this->reviewer?->first_name,
                'last_name'  => $this->reviewer?->last_name,
                'file'       => $this->reviewer?->file ? [
                    'id'       => $this->reviewer->file->id,
                    'name'     => $this->reviewer->file->name,
                    'file_url' => $this->reviewer->file->file_url,
                ] : null,
            ],
        ];
    }
}
