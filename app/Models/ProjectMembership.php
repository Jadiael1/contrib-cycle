<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProjectMembership',
    type: 'object',
    required: ['id', 'collective_project_id', 'user_id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 99),
        new OA\Property(property: 'collective_project_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64', example: 10),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'accepted', 'removed'],
            example: 'accepted'
        ),
        new OA\Property(property: 'accepted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'removed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'removed_by_user_id', type: 'integer', format: 'int64', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class ProjectMembership extends Model
{
    protected $fillable = [
        'collective_project_id',
        'user_id',
        'status',
        'accepted_at',
        'removed_at',
        'removed_by_user_id',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
    }
}
