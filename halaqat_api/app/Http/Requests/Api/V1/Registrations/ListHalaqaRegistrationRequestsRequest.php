<?php

namespace App\Http\Requests\Api\V1\Registrations;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class ListHalaqaRegistrationRequestsRequest extends StrictFormRequest
{
    protected array $allowedShape = ['state' => true, 'page' => true, 'per_page' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'state' => ['sometimes', Rule::in(['pending', 'completion_requested', 'accepted', 'rejected', 'withdrawn', 'cancelled'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
