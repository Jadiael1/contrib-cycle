<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ParticipantRegisterRequest',
    type: 'object',
    required: ['phone', 'first_name', 'last_name'],
    properties: [
        new OA\Property(property: 'phone', type: 'string', example: '+5581999999999'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
    ]
)]
class ParticipantRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ];
    }
}
