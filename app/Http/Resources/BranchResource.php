<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'staff_count' => $this->whenLoaded('staff', fn() => $this->staff->count()),
            'staff' => UserResource::collection($this->whenLoaded('staff')),
        ];
    }
}
