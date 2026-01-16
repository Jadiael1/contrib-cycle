<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectiveProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            // evita nulls quando você fez select parcial no controller (ex: public index)
            'description' => $this->whenHas('description'),

            'participant_limit' => $this->participant_limit,
            'amount_per_participant' => $this->amount_per_participant,

            'payment_interval' => $this->payment_interval,
            'payments_per_interval' => $this->payments_per_interval,

            'payment_methods' => CollectiveProjectPaymentMethodResource::collection(
                $this->whenLoaded('paymentMethods')
            ),

            'is_active' => $this->whenHas('is_active'),
            'created_by_user_id' => $this->whenHas('created_by_user_id'),

            'created_at' => $this->whenHas('created_at', fn() => $this->created_at?->toISOString()),
            'updated_at' => $this->whenHas('updated_at', fn() => $this->updated_at?->toISOString()),
        ];
    }
}
