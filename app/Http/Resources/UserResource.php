<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email_verified_at' => $this->email_verified_at,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'avatar_url' => $this->avatar_url ? config('app.domain') . '/storage/' . $this->avatar_url : null,
            'role' => $this->role,
            'company' => CompanyResource::make($this->whenLoaded('company')),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
