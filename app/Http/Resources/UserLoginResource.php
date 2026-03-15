<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoginResource extends JsonResource
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
            "business_name" => $this->business_name,
            "user_name" => $this->user_name,
            "email" => $this->email,
            "login_status" => $this->login_status,
            "country_code" => $this->country_code,
            "phone" => $this->phone,
            "role" => $this->role,
            "status" => $this->status,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "parent_id" => $this->parent_id,
            "user_id" => $this->user_id,
            "stripe_customer_id" => $this->stripe_customer_id,
            "status_detail" => $this->status_detail,
            "phone_number" => $this->phone_number,
            "party_ledgers_account_type" => $this->party_ledgers_account_type,
            "complete_profile" => $this->complete_profile,
            "selected_branch" => $this->selected_branch,
            "selected_branch" => $this->selected_branch,
        ];
    }
}
