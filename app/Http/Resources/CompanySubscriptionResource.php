<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (!$request->user() || is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->ulid,
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'subscription' => SubscriptionResource::make($this->whenLoaded('subscription')),
            'start_date' => $this->start_date?->toDateTimeString(),
            'end_date' => $this->end_date?->toDateTimeString(),
            'status' => $this->status?->value ?? null,
            'is_active' => $this->isActive(),
        ];
    }
}
