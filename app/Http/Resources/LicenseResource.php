<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LicenseResource extends JsonResource
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
            'authority'  => $this->authroity, // keep field name consistent
            'number'     => $this->number,
            'expiry'     => $this->expiry,
            'specialty'  => $this->specialty,
            'created_at' => $this->created_at,
            'file'       => $this->file ? [
                'id'       => $this->file->id,
                'file_url' => $this->file->file_url,
            ] : null,
        ];
    }
}
