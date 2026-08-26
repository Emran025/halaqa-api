<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\StrictFormRequest;

class ListAvailableTeachersRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'code' => true,
        'search' => true,
        'page' => true,
        'per_page' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:40'],
            'search' => ['sometimes', 'nullable', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
