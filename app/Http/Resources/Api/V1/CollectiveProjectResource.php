<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectResource',
    type: 'object',
    required: [
        'id',
        'title',
        'slug',
        'participant_limit',
        'amount_per_participant',
        'payment_interval',
        'payments_per_interval',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Community Cycle 2025'),
        new OA\Property(property: 'slug', type: 'string', example: 'community-cycle-2025'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Monthly contribution plan.'),
        new OA\Property(property: 'participant_limit', type: 'integer', example: 100),
        new OA\Property(property: 'amount_per_participant', type: 'string', format: 'decimal', example: '150.00'),
        new OA\Property(
            property: 'payment_interval',
            type: 'string',
            enum: ['week', 'month', 'year'],
            example: 'month'
        ),
        new OA\Property(property: 'payments_per_interval', type: 'integer', example: 4),
        new OA\Property(
            property: 'payment_methods',
            type: 'array',
            nullable: true,
            items: new OA\Items(ref: '#/components/schemas/CollectiveProjectPaymentMethodResource'),
            description: 'Present when payment methods are loaded.'
        ),
        new OA\Property(property: 'is_active', type: 'boolean', nullable: true, example: true),
        new OA\Property(property: 'created_by_user_id', type: 'integer', format: 'int64', nullable: true, example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
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
