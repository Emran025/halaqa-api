<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\StrictFormRequest;

class ForgotPasswordRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'email' => true,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
