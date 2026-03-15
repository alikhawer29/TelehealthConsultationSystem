<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
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
            'id'              => $this->id,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'email'       => $this->email,
            'login_status'       => $this->login_status,
            'country_code'       => $this->country_code,
            'phone'       => $this->phone,
            'role'       => $this->role,
            'professional'       => $this->professional,
            'about'       => $this->about,
            'experience'       => $this->experience,
            'languages'       => $this->languages,
            'status'       => $this->status,
            'status_detail'       => $this->status_detail,
            'phone_number'       => $this->phone_number,
            'total_patients'      => $this->total_patients,
            'rating_avg'      => round($this->rating_avg, 2),
            'total_reviews'   => $this->total_reviews,
            'file'   => $this->file,
            'education'       => EducationResource::collection($this->whenLoaded('education')),
            'license'         => new LicenseResource($this->whenLoaded('license')),
            'session_type'    => SessionTypeResource::collection($this->whenLoaded('sessionType')),
            'reviews'         => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
