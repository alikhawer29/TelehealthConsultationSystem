<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $service = $this->type === 'service'
            ? $this->service
            : $this->bundleService;

        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id,
            'charges' => $this->charges,
            'type' => $this->type,
            'service' => $service ? [
                'id' => $service->id,
                'name' => $service->name ?? $service->bundle_name,
                'price' => $service->price,
                'type' => $service->type,
                'file' => $service->file ? [
                    'path' => $service->file->path,
                    'fileable_id' => $service->file->fileable_id,
                    'fileable_type' => $service->file->fileable_type,
                    'file_url' => $service->file->file_url,
                    'id' => $service->file->id
                ] : null
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
