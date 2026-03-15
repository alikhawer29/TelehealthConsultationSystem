<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
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
            'doctor' => $this->doctor,
            'patient' => $this->patient,
            'appointment' => $this->appointment,
            'medication' => $this->medication,
            'dosage' => $this->dosage,
            'status' => $this->status,
            'role' => $this->role,
            'file' => $this->file,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'file_name' => $this->file_name,
            'creator' => $this->creator
        ];
    }
}
