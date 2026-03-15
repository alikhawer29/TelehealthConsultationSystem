<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'login_status' => $this->login_status,
            'country_code' => $this->country_code,
            'flag_type' => $this->flag_type,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'status_detail' => $this->status_detail,
            'phone_number' => $this->phone_number,
            'complete_profile' => $this->complete_profile,
            'scan_type' => $this->scan_type,
            'file' => new FileResource($this->whenLoaded('file')),
            'family_members' => $this->familyMembers, // optional: you can convert this to resource too
            'insurance' => new InsuranceResource($this->whenLoaded('insurance')),
            'education'       => EducationResource::collection($this->whenLoaded('education')),
            'license'         => new LicenseResource($this->whenLoaded('license')),
            'session_type'    => SessionTypeResource::collection($this->whenLoaded('sessionType')),
            'reviews'         => ReviewResource::collection($this->whenLoaded('reviews')),
            'experience'       => $this->experience,
            'languages'       => $this->languages,
            'about'       => $this->about,
            'professional'       => $this->professional,

        ];
    }
}
