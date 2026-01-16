<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
