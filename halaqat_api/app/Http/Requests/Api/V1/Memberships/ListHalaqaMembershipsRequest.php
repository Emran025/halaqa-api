<?php

namespace App\Http\Requests\Api\V1\Memberships;

use App\Http\Requests\StrictFormRequest;

class ListHalaqaMembershipsRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'status' => true,
        'search' => true,
        'page' => true,
        'per_page' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isActive() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:active,inactive,removed'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
