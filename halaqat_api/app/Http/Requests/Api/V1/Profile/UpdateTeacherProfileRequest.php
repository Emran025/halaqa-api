<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTeacherProfileRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true, 'birth_date' => true, 'gender' => true, 'country' => true,
        'city' => true, 'residence' => true, 'phone' => true, 'phone_zone' => true,
        'whatsapp_phone' => true, 'whatsapp_zone' => true, 'qualification' => true,
        'experience_years' => true, 'available_time' => true, 'bio' => true, 'max_halaqas' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:120'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:200'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_zone' => ['sometimes', 'nullable', 'string', 'max:8'],
            'whatsapp_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp_zone' => ['sometimes', 'nullable', 'string', 'max:8'],
            'qualification' => ['sometimes', 'nullable', 'string', 'max:250'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'between:0,80'],
            'available_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'max_halaqas' => ['sometimes', 'nullable', 'integer', 'min:0'],
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
