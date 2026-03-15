<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'id'                                => $this->id,
            'name'                              => $this->name,
            'price'                             => $this->price,
            'type'                              => $this->type,
            'status'                            => $this->status,
            'status_detail'                     => $this->status_detail,
            'about'                             => $this->about,
            'conditions_to_treat'               => $this->conditions_to_treat,
            'what_to_expect'                    => $this->what_to_expect,
            'preparations'                      => $this->preparations,
            'ingredients'                       => $this->ingredients,
            'restriction'                       => $this->restriction,
            'general_information'               => $this->general_information,
            'key_services_included'             => $this->key_services_included,
            'what_to_expect_during_the_sessions' => $this->what_to_expect_during_the_sessions,
            'preparations_and_precautions'      => $this->preparations_and_precautions,
            'who_should_consider_this_service'  => $this->who_should_consider_this_service,
            'why_to_get_tested'                 => $this->why_to_get_tested,
            'specimen_type'                     => $this->specimen_type,
            'preparation_needed'                => $this->preparation_needed,
            'administration_time'               => $this->administration_time,
            'parameters_included'               => $this->parameters_included,
            'fasting_requirments'               => $this->fasting_requirments,
            'turnaround_time'                   => $this->turnaround_time,
            'when_to_get_tested'                => $this->when_to_get_tested,
            'precautions'                       => $this->precautions,
            'file'                              => $this->whenLoaded('file', fn() => new FileResource($this->file)),
            'icon'                              => $this->whenLoaded('icon', fn() => new FileResource($this->icon)),
            'reviews'                           => $this->whenLoaded('reviews', fn() => $this->reviews),

        ];
    }
}
