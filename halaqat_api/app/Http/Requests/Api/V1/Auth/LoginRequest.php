<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\StrictFormRequest;

class LoginRequest extends StrictFormRequest
{
    protected array $allowedFields = ['email', 'password'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
