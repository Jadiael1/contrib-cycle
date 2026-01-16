<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
