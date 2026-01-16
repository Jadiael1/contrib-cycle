<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
