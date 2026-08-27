<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\StrictFormRequest;

class ResetPasswordRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'email' => true,
        'token' => true,
        'password' => true,
        'password_confirmation' => true,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
