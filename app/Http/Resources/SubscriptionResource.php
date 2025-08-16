<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'price' => $this->price,
            'duration_days' => $this->duration_days,
            'duration' => formatDaysToYearsMonthsDays($this->duration_days),
            'description' => $this->description,
            'creator' => UserResource::make($this->whenLoaded('creator')),
        ];
    }
}
