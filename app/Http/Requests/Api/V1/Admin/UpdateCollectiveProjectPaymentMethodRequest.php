<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\CollectiveProjectPaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCollectiveProjectPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** @var CollectiveProjectPaymentMethod|null $method */
        $method = $this->route('paymentMethod');

        $type = $this->input('payment_method_type');
        if (!is_string($type) && $method) {
            $type = $method->payment_method_type;
        }

        $payload = $this->input('payment_method_payload');

        $this->merge([
            'payment_method_type' => $type,
            'label' => is_string($this->label) ? trim($this->label) : $this->label,
            'payment_method_payload' => is_array($payload) ? $payload : $payload, // mantém null se não veio
        ]);
    }

    public function rules(): array
    {
        return [
            // sempre resolvido (do request ou do model via prepareForValidation)
            'payment_method_type' => ['required', Rule::in(['pix', 'bank_transfer'])],

            'payment_method_payload' => ['sometimes', 'array'],
            'label' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:65000'],

            // Só exige os campos se você estiver enviando payment_method_payload no patch
            'payment_method_payload.pix_key' => ['required_with:payment_method_payload', 'required_if:payment_method_type,pix', 'string', 'max:255'],
            'payment_method_payload.pix_holder_name' => ['required_with:payment_method_payload', 'required_if:payment_method_type,pix', 'string', 'max:150'],

            'payment_method_payload.bank_name' => ['required_with:payment_method_payload', 'required_if:payment_method_type,bank_transfer', 'string', 'max:120'],
            'payment_method_payload.bank_code' => ['nullable', 'string', 'max:20'],
            'payment_method_payload.agency' => ['required_with:payment_method_payload', 'required_if:payment_method_type,bank_transfer', 'string', 'max:20'],
            'payment_method_payload.account_number' => ['required_with:payment_method_payload', 'required_if:payment_method_type,bank_transfer', 'string', 'max:30'],
            'payment_method_payload.account_type' => ['nullable', Rule::in(['checking', 'savings'])],
            'payment_method_payload.account_holder_name' => ['required_with:payment_method_payload', 'required_if:payment_method_type,bank_transfer', 'string', 'max:150'],
            'payment_method_payload.document' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var CollectiveProjectPaymentMethod|null $method */
                $method = $this->route('paymentMethod');

                if (
                    $method &&
                    $this->has('payment_method_type') &&
                    $this->input('payment_method_type') !== $method->payment_method_type &&
                    ! $this->has('payment_method_payload')
                ) {
                    $validator->errors()->add(
                        'payment_method_payload',
                        'payment_method_payload is required when changing payment_method_type.'
                    );
                }
            },
        ];
    }
}
