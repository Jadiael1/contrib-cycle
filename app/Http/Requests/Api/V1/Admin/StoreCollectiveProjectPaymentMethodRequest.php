<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectiveProjectPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payment_method_payload');

        $this->merge([
            'label' => is_string($this->label) ? trim($this->label) : $this->label,
            'payment_method_payload' => is_array($payload) ? $payload : [],
        ]);
    }

    public function rules(): array
    {
        return [
            'payment_method_type' => ['required', Rule::in(['pix', 'bank_transfer'])],
            'payment_method_payload' => ['required', 'array'],

            'label' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:65000'],

            // PIX
            'payment_method_payload.pix_key' => ['required_if:payment_method_type,pix', 'string', 'max:255'],
            'payment_method_payload.pix_holder_name' => ['required_if:payment_method_type,pix', 'string', 'max:150'],

            // Bank transfer
            'payment_method_payload.bank_name' => ['required_if:payment_method_type,bank_transfer', 'string', 'max:120'],
            'payment_method_payload.bank_code' => ['nullable', 'string', 'max:20'],
            'payment_method_payload.agency' => ['required_if:payment_method_type,bank_transfer', 'string', 'max:20'],
            'payment_method_payload.account_number' => ['required_if:payment_method_type,bank_transfer', 'string', 'max:30'],
            'payment_method_payload.account_type' => ['nullable', Rule::in(['checking', 'savings'])],
            'payment_method_payload.account_holder_name' => ['required_if:payment_method_type,bank_transfer', 'string', 'max:150'],
            'payment_method_payload.document' => ['nullable', 'string', 'max:30'],
        ];
    }
}
