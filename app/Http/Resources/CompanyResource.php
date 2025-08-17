<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'admin' => UserResource::make($this->whenLoaded('admin')),

            'staff_count' => $this->whenLoaded('staff', fn() => $this->staff->count()),
            'staff' => UserResource::collection($this->whenLoaded('staff')),

            'subscriptions' => $this->whenLoaded('subscriptions', fn() =>
                CompanySubscriptionResource::collection($this->subscriptions)
            ),

            'active_subscription' => $this->whenLoaded('activeSubscription', fn() =>
                CompanySubscriptionResource::make($this->activeSubscription)
            ),
        ];
    }
}
