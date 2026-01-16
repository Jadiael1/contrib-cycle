<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\CollectiveProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentsStatusReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'year' => $this->input('year', now()->year),
            'format' => $this->input('format', 'csv'),
        ]);
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'week_of_month' => ['nullable', 'integer', 'min:1', 'max:6'],
            'format' => ['required', Rule::in(['csv', 'xlsx'])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var CollectiveProject|null $project */
                $project = $this->route('project');
                if (!$project) return;

                $interval = $project->payment_interval;

                $hasMonth = $this->filled('month');
                $hasWeek  = $this->filled('week_of_month');

                if ($interval === 'week') {
                    if (!$hasMonth) {
                        $validator->errors()->add('month', 'month is required for weekly projects.');
                    }
                }

                if ($interval === 'month') {
                    if ($hasWeek) {
                        $validator->errors()->add('week_of_month', 'week_of_month is not allowed for monthly projects.');
                    }
                }

                if ($interval === 'year') {
                    if ($hasMonth) $validator->errors()->add('month', 'month is not allowed for yearly projects.');
                    if ($hasWeek)  $validator->errors()->add('week_of_month', 'week_of_month is not allowed for yearly projects.');
                }

                if ($hasWeek && !$hasMonth) {
                    $validator->errors()->add('week_of_month', 'week_of_month requires month.');
                }
            }
        ];
    }
}
