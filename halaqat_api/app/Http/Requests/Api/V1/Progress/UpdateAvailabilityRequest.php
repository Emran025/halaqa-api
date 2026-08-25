<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class UpdateAvailabilityRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'timezone' => true, 'preferred_session_duration_minutes' => true,
        'weekly_slots' => ['*' => ['day_of_week' => true, 'from' => true, 'to' => true, 'preferred' => true]],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true || $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['required', 'timezone:all'],
            'preferred_session_duration_minutes' => ['sometimes', 'integer', 'between:10,180'],
            'weekly_slots' => ['required', 'array', 'min:1'],
            'weekly_slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'weekly_slots.*.from' => ['required', 'date_format:H:i'],
            'weekly_slots.*.to' => ['required', 'date_format:H:i', 'after:weekly_slots.*.from'],
            'weekly_slots.*.preferred' => ['sometimes', 'boolean'],
        ];
    }
}
