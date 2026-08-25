<?php

namespace App\Http\Requests\Api\V1\Registrations;

use App\Http\Requests\StrictFormRequest;

class CompletionRequest extends StrictFormRequest
{
    protected array $allowedFields = ['required_fields', 'note'];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'required_fields' => ['required', 'array', 'min:1'],
            'required_fields.*' => ['required', 'string', 'max:120'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
