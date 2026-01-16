<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectPaymentMethod',
    type: 'object',
    required: [
        'id',
        'collective_project_id',
        'payment_method_type',
        'is_active',
        'sort_order',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 12),
        new OA\Property(property: 'collective_project_id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(
            property: 'payment_method_type',
            type: 'string',
            enum: ['pix', 'bank_transfer'],
            example: 'pix'
        ),
        new OA\Property(
            property: 'payment_method_payload',
            nullable: true,
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadPix'),
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadBankTransfer'),
            ]
        ),
        new OA\Property(property: 'label', type: 'string', nullable: true, example: 'Primary'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CollectiveProjectPaymentMethod extends Model
{
    protected $table = 'collective_project_payment_methods';

    protected $fillable = [
        'collective_project_id',
        'payment_method_type',
        'payment_method_payload',
        'label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        // criptografa em repouso e entrega como array no PHP
        'payment_method_payload' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['payment_method_payload'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }
}
