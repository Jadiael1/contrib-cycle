<?php

namespace App\Http\Requests\Api\V1;

use App\Models\CollectiveProject;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCollectiveProjectPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'year' => $this->input('year', (int) now()->year),
            'month' => $this->input('month'), // pode vir null
            'week_of_month' => $this->input('week_of_month'),
            'sequence' => $this->input('sequence', 1),
            'paid_at' => $this->input('paid_at', now()->toISOString()),
        ]);
    }

    public function rules(): array
    {
        /** @var CollectiveProject|null $project */
        $project = $this->route('project');

        $interval = $project?->payment_interval ?? 'month';
        $maxSeq = (int) ($project?->payments_per_interval ?? 1);

        $rules = [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'sequence' => ['required', 'integer', 'min:1', 'max:' . $maxSeq],
            'paid_at' => ['required', 'date'],

            // comprovante opcional
            'receipt' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];

        if ($interval === 'week') {
            $rules['month'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['week_of_month'] = ['required', 'integer', 'min:1', 'max:6']; // max real validaremos no after()
        }

        if ($interval === 'month') {
            $rules['month'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['week_of_month'] = ['prohibited'];
        }

        if ($interval === 'year') {
            $rules['month'] = ['prohibited'];
            $rules['week_of_month'] = ['prohibited'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var CollectiveProject|null $project */
                $project = $this->route('project');
                if (! $project) return;

                if ($project->payment_interval !== 'week') return;

                $year = (int) $this->input('year');
                $month = (int) $this->input('month');
                $week = (int) $this->input('week_of_month');

                $weeksInMonth = $this->weeksInMonth($year, $month);

                if ($week < 1 || $week > $weeksInMonth) {
                    $validator->errors()->add(
                        'week_of_month',
                        "Invalid week_of_month for {$year}-{$month}. Max is {$weeksInMonth}."
                    );
                }
            },
        ];
    }

    private function weeksInMonth(int $year, int $month): int
    {
        $firstDay = Carbon::create($year, $month, 1);
        $days = (int) $firstDay->daysInMonth;
        $offset = (int) $firstDay->dayOfWeekIso - 1; // 0..6 (Mon..Sun)
        return (int) ceil(($offset + $days) / 7);
    }
}
