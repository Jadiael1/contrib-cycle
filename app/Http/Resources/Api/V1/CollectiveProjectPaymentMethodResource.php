<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectPaymentMethodResource',
    type: 'object',
    required: ['id', 'type', 'sort_order', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['pix', 'bank_transfer'],
            example: 'pix'
        ),
        new OA\Property(property: 'label', type: 'string', nullable: true, example: 'Primary'),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(
            property: 'payload',
            nullable: true,
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadPix'),
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadBankTransfer'),
            ],
            description: 'Returned only for authenticated tokens with access to the payload.'
        ),
    ]
)]
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
