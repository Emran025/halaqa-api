<?php

namespace App\Http\Requests\Api\V1\Halaqas;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class StoreHalaqaRequest extends StrictFormRequest
{
    protected array $allowedFields = [
        'name', 'description', 'gender', 'country', 'residence', 'max_students', 'timezone', 'status',
    ];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'country' => ['required', 'string', 'min:2', 'max:100'],
            'residence' => ['required', 'string', 'min:1', 'max:200'],
            'max_students' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
