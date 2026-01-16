<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectiveProjectPaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeePayload = $user && ($user->tokenCan('admin') || $user->tokenCan('participant'));

        $attrs = $this->resource->getAttributes();
        $payloadLoaded = array_key_exists('payment_method_payload', $attrs);

        return [
            'id' => $this->id,
            'type' => $this->payment_method_type,
            'label' => $this->label,
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,

            'payload' => $this->when($canSeePayload && $payloadLoaded, $this->payment_method_payload),
        ];
    }
}
