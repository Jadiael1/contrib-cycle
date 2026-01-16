<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProjectMemberResource',
    type: 'object',
    required: ['membership_id', 'status', 'user'],
    properties: [
        new OA\Property(property: 'membership_id', type: 'integer', format: 'int64', example: 99),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'accepted', 'removed'],
            example: 'accepted'
        ),
        new OA\Property(property: 'accepted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'removed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(
            property: 'user',
            type: 'object',
            required: ['id', 'phone', 'first_name', 'last_name'],
            properties: [
                new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 10),
                new OA\Property(property: 'phone', type: 'string', example: '+5581999999999'),
                new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
            ]
        ),
    ]
)]
class ProjectMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'membership_id' => $this->id,
            'status' => $this->status,
            'accepted_at' => $this->accepted_at?->toISOString(),
            'removed_at' => $this->removed_at?->toISOString(),

            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ],
        ];
    }
}
