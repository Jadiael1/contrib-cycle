<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CollectiveProjectPaymentResource',
    type: 'object',
    required: ['id', 'amount', 'paid_at', 'period'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 55),
        new OA\Property(property: 'amount', type: 'string', format: 'decimal', example: '150.00'),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2025-05-10T12:00:00Z'),
        new OA\Property(property: 'receipt_path', type: 'string', nullable: true, example: 'project-receipts/1/10/file.pdf'),
        new OA\Property(property: 'period', ref: '#/components/schemas/PaymentPeriod'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CollectiveProjectPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $month = (int) $this->period_month;
        $week = (int) $this->period_week_of_month;

        $period = [
            'year' => (int) $this->period_year,
            'month' => $month > 0 ? $month : null,
            'week_of_month' => $week > 0 ? $week : null,
            'sequence' => (int) $this->sequence_in_period,
        ];

        return [
            'id' => $this->id,
            'amount' => (string) $this->amount,
            'paid_at' => $this->paid_at?->toISOString(),
            'receipt_path' => $this->receipt_path, // opcional: você pode omitir se não quiser expor path
            'period' => $period,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
