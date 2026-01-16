<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Invalid credentials.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'The given data was invalid.'
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: [
                'field' => [
                    'The field is required.',
                ],
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Logged out.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'AuthTokenResponse',
    type: 'object',
    required: ['token'],
    properties: [
        new OA\Property(
            property: 'token',
            type: 'string',
            description: 'Bearer token.',
            example: '1|abc123token'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ParticipantRegisterResponse',
    type: 'object',
    required: ['user_id', 'message'],
    properties: [
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            example: 10
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Registered. Now you can join a project and confirm participation.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProjectMembershipJoinResponse',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Participation confirmed.'
        ),
        new OA\Property(
            property: 'membership_id',
            type: 'integer',
            format: 'int64',
            nullable: true,
            example: 42
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProjectMembershipStatus',
    type: 'object',
    required: ['is_member'],
    properties: [
        new OA\Property(
            property: 'is_member',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'accepted', 'removed'],
            nullable: true,
            example: 'accepted'
        ),
        new OA\Property(
            property: 'accepted_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2025-01-10T12:00:00Z'
        ),
        new OA\Property(
            property: 'removed_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: null
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProjectMembershipMeta',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'accepted', 'removed'],
            nullable: true,
            example: 'accepted'
        ),
        new OA\Property(
            property: 'accepted_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2025-01-10T12:00:00Z'
        ),
        new OA\Property(
            property: 'removed_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'blocked',
            type: 'boolean',
            example: false
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProjectStats',
    type: 'object',
    required: ['accepted_count', 'available_slots', 'is_full'],
    properties: [
        new OA\Property(
            property: 'accepted_count',
            type: 'integer',
            example: 12
        ),
        new OA\Property(
            property: 'available_slots',
            type: 'integer',
            example: 8
        ),
        new OA\Property(
            property: 'is_full',
            type: 'boolean',
            example: false
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProjectCounts',
    type: 'object',
    required: ['pending', 'accepted', 'removed'],
    properties: [
        new OA\Property(
            property: 'pending',
            type: 'integer',
            example: 3
        ),
        new OA\Property(
            property: 'accepted',
            type: 'integer',
            example: 10
        ),
        new OA\Property(
            property: 'removed',
            type: 'integer',
            example: 1
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaymentPeriod',
    type: 'object',
    required: ['year', 'sequence'],
    properties: [
        new OA\Property(
            property: 'year',
            type: 'integer',
            example: 2025
        ),
        new OA\Property(
            property: 'month',
            type: 'integer',
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: 'week_of_month',
            type: 'integer',
            nullable: true,
            example: 2
        ),
        new OA\Property(
            property: 'sequence',
            type: 'integer',
            example: 1
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaymentOptionsResponse',
    type: 'object',
    required: ['payment_interval', 'payments_per_interval', 'sequence_range'],
    properties: [
        new OA\Property(
            property: 'payment_interval',
            type: 'string',
            enum: ['week', 'month', 'year'],
            example: 'month'
        ),
        new OA\Property(
            property: 'payments_per_interval',
            type: 'integer',
            example: 4
        ),
        new OA\Property(
            property: 'sequence_range',
            type: 'object',
            required: ['min', 'max'],
            properties: [
                new OA\Property(property: 'min', type: 'integer', example: 1),
                new OA\Property(property: 'max', type: 'integer', example: 4),
            ]
        ),
        new OA\Property(
            property: 'weeks_in_month',
            type: 'integer',
            nullable: true,
            example: 4
        ),
        new OA\Property(
            property: 'weeks',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                required: ['value', 'label'],
                properties: [
                    new OA\Property(property: 'value', type: 'integer', example: 1),
                    new OA\Property(property: 'label', type: 'string', example: 'First Week'),
                ]
            )
        ),
        new OA\Property(
            property: 'hint',
            type: 'string',
            nullable: true,
            example: 'Provide year and month query params to get weeks list.'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaymentMethodPayloadPix',
    type: 'object',
    required: ['pix_key', 'pix_holder_name'],
    properties: [
        new OA\Property(
            property: 'pix_key',
            type: 'string',
            example: '00020126580014br.gov.bcb.pix...'
        ),
        new OA\Property(
            property: 'pix_holder_name',
            type: 'string',
            example: 'John Doe'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaymentMethodPayloadBankTransfer',
    type: 'object',
    required: ['bank_name', 'agency', 'account_number', 'account_holder_name'],
    properties: [
        new OA\Property(
            property: 'bank_name',
            type: 'string',
            example: 'Bank SA'
        ),
        new OA\Property(
            property: 'bank_code',
            type: 'string',
            nullable: true,
            example: '001'
        ),
        new OA\Property(
            property: 'agency',
            type: 'string',
            example: '1234'
        ),
        new OA\Property(
            property: 'account_number',
            type: 'string',
            example: '987654-3'
        ),
        new OA\Property(
            property: 'account_type',
            type: 'string',
            enum: ['checking', 'savings'],
            nullable: true,
            example: 'checking'
        ),
        new OA\Property(
            property: 'account_holder_name',
            type: 'string',
            example: 'John Doe'
        ),
        new OA\Property(
            property: 'document',
            type: 'string',
            nullable: true,
            example: '123.456.789-10'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    type: 'object',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true),
        new OA\Property(property: 'last', type: 'string', nullable: true),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMetaLink',
    type: 'object',
    required: ['label', 'active'],
    properties: [
        new OA\Property(property: 'url', type: 'string', nullable: true),
        new OA\Property(property: 'label', type: 'string', example: '1'),
        new OA\Property(property: 'active', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    required: ['current_page', 'last_page', 'per_page', 'total'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaginationMetaLink')
        ),
        new OA\Property(property: 'path', type: 'string', example: 'https://api.example.com/v1/items'),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'to', type: 'integer', nullable: true),
        new OA\Property(property: 'total', type: 'integer', example: 55),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedProjectMembersResponse',
    type: 'object',
    required: ['data', 'links', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ProjectMemberResource')
        ),
        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedReportsResponse',
    type: 'object',
    required: ['data', 'links', 'meta'],
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CollectiveProjectReportResource')
        ),
        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'CollectiveProjectDetailResponse',
    type: 'object',
    required: ['data', 'membership', 'stats'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/CollectiveProjectResource'),
        new OA\Property(property: 'membership', ref: '#/components/schemas/ProjectMembershipMeta'),
        new OA\Property(property: 'stats', ref: '#/components/schemas/ProjectStats'),
    ]
)]
#[OA\Schema(
    schema: 'CollectiveProjectAdminDetailResponse',
    type: 'object',
    required: ['data', 'counts', 'stats'],
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/CollectiveProjectResource'),
        new OA\Property(property: 'counts', ref: '#/components/schemas/ProjectCounts'),
        new OA\Property(property: 'stats', ref: '#/components/schemas/ProjectStats'),
    ]
)]
class ApiSchemas
{
}
