<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class SessionDecisionRequest extends StrictFormRequest
{
    protected array $allowedShape = ['note' => true];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return ['note' => ['sometimes', 'nullable', 'string', 'max:1000']];
    }
}
