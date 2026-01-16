<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\CollectiveProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePaymentStatusReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var CollectiveProject|null $project */
        $project = $this->route('project');
        $interval = $project?->payment_interval ?? 'month';

        $rules = [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'status_scope' => ['nullable', Rule::in(['accepted_only', 'include_removed'])],
        ];

        if ($interval === 'week') {
            $rules['month'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['week_of_month'] = ['nullable', 'integer', 'min:1', 'max:6'];
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
}
