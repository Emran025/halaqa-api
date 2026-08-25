<?php

namespace App\Http\Requests\Api\V1\Registrations;

use App\Http\Requests\StrictFormRequest;

class DecisionNoteRequest extends StrictFormRequest
{
    protected array $allowedFields = ['note'];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return ['note' => ['sometimes', 'nullable', 'string', 'max:1000']];
    }
}
