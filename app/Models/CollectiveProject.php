<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProject',
    type: 'object',
    required: [
        'id',
        'title',
        'slug',
        'participant_limit',
        'amount_per_participant',
        'payment_interval',
        'payments_per_interval',
        'is_active',
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
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'created_by_user_id', type: 'integer', format: 'int64', nullable: true, example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CollectiveProject extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'participant_limit',
        'amount_per_participant',
        'payment_interval',
        'payments_per_interval',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount_per_participant' => 'decimal:2',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class, 'collective_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(CollectiveProjectPaymentMethod::class, 'collective_project_id')
            ->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollectiveProjectPayment::class, 'collective_project_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CollectiveProjectReport::class, 'collective_project_id');
    }
}
