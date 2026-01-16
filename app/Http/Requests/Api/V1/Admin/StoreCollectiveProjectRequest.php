<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreCollectiveProjectRequest',
    type: 'object',
    required: [
        'title',
        'participant_limit',
        'amount_per_participant',
        'payment_interval',
        'payments_per_interval',
        'payment_method_type',
        'payment_method_payload',
    ],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Community Cycle 2025'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Monthly contribution plan.'),
        new OA\Property(property: 'participant_limit', type: 'integer', example: 100),
        new OA\Property(property: 'amount_per_participant', type: 'number', format: 'float', example: 150.0),
        new OA\Property(
            property: 'payment_interval',
            type: 'string',
            enum: ['week', 'month', 'year'],
            example: 'month'
        ),
        new OA\Property(property: 'payments_per_interval', type: 'integer', example: 4),
        new OA\Property(
            property: 'payment_method_type',
            type: 'string',
            enum: ['pix', 'bank_transfer'],
            example: 'pix'
        ),
        new OA\Property(
            property: 'payment_method_payload',
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadPix'),
                new OA\Schema(ref: '#/components/schemas/PaymentMethodPayloadBankTransfer'),
            ]
        ),
    ]
)]
class StoreCollectiveProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payment_method_payload');

        $this->merge([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
            'payment_method_payload' => is_array($payload) ? $payload : [],
            'payments_per_interval' => $this->input('payments_per_interval', 1),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],

            'participant_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'amount_per_participant' => ['required', 'numeric', 'min:0.01'],

            'payment_interval' => ['required', Rule::in(['week', 'month', 'year'])],
            'payments_per_interval' => ['required', 'integer', 'min:1', 'max:365'],

            'payment_method_type' => ['required', Rule::in(['pix', 'bank_transfer'])],
            'payment_method_payload' => ['required', 'array'],

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

    public function messages(): array
    {
        return [
            'payment_method_payload.pix_key.required_if' => 'pix_key is required when payment_method_type is pix.',
            'payment_method_payload.bank_name.required_if' => 'bank_name is required when payment_method_type is bank_transfer.',
        ];
    }
}
