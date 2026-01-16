<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectPayment',
    type: 'object',
    required: [
        'id',
        'collective_project_id',
        'user_id',
        'period_year',
        'sequence_in_period',
        'amount',
        'paid_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 55),
        new OA\Property(property: 'collective_project_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', format: 'int64', example: 10),
        new OA\Property(property: 'period_year', type: 'integer', example: 2025),
        new OA\Property(property: 'period_month', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'period_week_of_month', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'sequence_in_period', type: 'integer', example: 1),
        new OA\Property(property: 'amount', type: 'string', format: 'decimal', example: '150.00'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2025-05-10T12:00:00Z'),
        new OA\Property(property: 'receipt_path', type: 'string', nullable: true, example: 'project-receipts/1/10/file.pdf'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CollectiveProjectPayment extends Model
{
    protected $fillable = [
        'collective_project_id',
        'user_id',
        'period_year',
        'period_month',
        'period_week_of_month',
        'sequence_in_period',
        'amount',
        'paid_at',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
