<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AdminLoginRequest',
    type: 'object',
    required: ['username', 'password'],
    properties: [
        new OA\Property(property: 'username', type: 'string', example: 'admin'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
    ]
)]
class AdminLoginRequest extends FormRequest
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
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
