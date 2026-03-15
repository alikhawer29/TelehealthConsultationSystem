<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhysicianResource extends JsonResource
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
            'id'               => $this->id,
            'first_name'       => $this->first_name,
            'last_name'        => $this->last_name,
            'email'            => $this->email,
            'login_status'     => $this->login_status,
            'country_code'     => $this->country_code,
            'phone'            => $this->phone,
            'role'             => $this->role,
            'professional'     => $this->professional,
            'about'            => $this->about,
            'experience'       => $this->experience,
            'languages'        => $this->languages,
            'status'           => $this->status,
            'status_detail'    => $this->status_detail,
            'phone_number'     => $this->phone_number,
            'file'      => new FileResource($this->whenLoaded('file')),
            'education' => EducationResource::collection($this->whenLoaded('education')),
            'license'   => new LicenseResource($this->whenLoaded('license')),
        ];
    }
}
