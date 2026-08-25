<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true,
        'phone' => true,
        'memorization_level' => true,
        'review_level' => true,
    ];

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'memorization_level' => ['sometimes', 'nullable', 'string', 'max:120'],
            'review_level' => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->isMethod('PATCH') && $this->all() === []) {
                $validator->errors()->add('_schema', 'At least one field is required.');
            }
        });
    }
}
