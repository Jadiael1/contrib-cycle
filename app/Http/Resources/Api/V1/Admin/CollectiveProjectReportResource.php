<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectReportResource',
    type: 'object',
    required: ['id', 'type', 'status', 'filters', 'file'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 5),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['payment_status'],
            example: 'payment_status'
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['processing', 'completed', 'failed'],
            example: 'processing'
        ),
        new OA\Property(
            property: 'filters',
            type: 'object',
            properties: [
                new OA\Property(property: 'year', type: 'integer', example: 2025),
                new OA\Property(property: 'month', type: 'integer', nullable: true, example: 5),
                new OA\Property(property: 'week_of_month', type: 'integer', nullable: true, example: 2),
                new OA\Property(
                    property: 'status_scope',
                    type: 'string',
                    enum: ['accepted_only', 'include_removed'],
                    example: 'accepted_only'
                ),
                new OA\Property(property: 'disk', type: 'string', example: 'local'),
            ]
        ),
        new OA\Property(
            property: 'file',
            type: 'object',
            properties: [
                new OA\Property(property: 'name', type: 'string', nullable: true, example: 'payment-status.csv'),
                new OA\Property(property: 'mime_type', type: 'string', nullable: true, example: 'text/csv'),
                new OA\Property(property: 'size', type: 'integer', nullable: true, example: 102400),
                new OA\Property(property: 'generated_at', type: 'string', format: 'date-time', nullable: true),
            ]
        ),
        new OA\Property(property: 'download_url', type: 'string', nullable: true),
        new OA\Property(property: 'created_by_user_id', type: 'integer', format: 'int64', nullable: true, example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'error_message', type: 'string', nullable: true),
    ]
)]
class CollectiveProjectReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // mapeia status interno -> status que o front quer
        $apiStatus = match ((string) $this->status) {
            'ready' => 'completed',
            'pending' => 'processing',
            default => (string) $this->status,
        };

        $downloadUrl = null;

        if ($apiStatus === 'completed' && $this->path) {
            $downloadUrl = url("/api/v1/admin/projects/{$this->collective_project_id}/reports/{$this->id}/download");
        }

        return [
            'id' => (int) $this->id,
            'type' => (string) $this->type,
            'status' => $apiStatus,

            'filters' => $this->filters ?? [],

            'file' => [
                'name' => $this->file_name,
                'mime_type' => $this->mime_type,
                'size' => $this->file_size,
                'generated_at' => $this->generated_at?->toISOString(),
            ],

            'download_url' => $downloadUrl,

            'created_by_user_id' => $this->created_by_user_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'error_message' => $this->when($apiStatus === 'failed', $this->error_message),
        ];
    }
}
