<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectReport',
    type: 'object',
    required: [
        'id',
        'collective_project_id',
        'type',
        'status',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 5),
        new OA\Property(property: 'collective_project_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'created_by_user_id', type: 'integer', format: 'int64', nullable: true, example: 1),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['payment_status'],
            example: 'payment_status'
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'ready', 'failed'],
            example: 'pending'
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
        new OA\Property(property: 'disk', type: 'string', nullable: true, example: 'local'),
        new OA\Property(property: 'path', type: 'string', nullable: true, example: 'reports/1/payment-status.csv'),
        new OA\Property(property: 'file_name', type: 'string', nullable: true, example: 'payment-status.csv'),
        new OA\Property(property: 'mime_type', type: 'string', nullable: true, example: 'text/csv'),
        new OA\Property(property: 'file_size', type: 'integer', nullable: true, example: 102400),
        new OA\Property(property: 'generated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'error_message', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CollectiveProjectReport extends Model
{
    protected $fillable = [
        'collective_project_id',
        'created_by_user_id',
        'report_type',
        'status',
        'filters',
        'file_disk',
        'file_path',
        'file_name',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
        'generated_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
