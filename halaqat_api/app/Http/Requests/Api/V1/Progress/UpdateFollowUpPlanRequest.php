<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class UpdateFollowUpPlanRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'frequency' => true, 'starts_on' => true, 'ends_on' => true,
        'details' => ['*' => ['task_type' => true, 'unit' => true, 'amount' => true, 'notes' => true]],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true || $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'frequency' => ['required', Rule::in(['daily', 'onceAWeek', 'twiceAWeek', 'thriceAWeek'])],
            'starts_on' => ['sometimes', 'nullable', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_on'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.task_type' => ['required', Rule::in(['memorization', 'review', 'recitation'])],
            'details.*.unit' => ['required', Rule::in(['juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'])],
            'details.*.amount' => ['required', 'numeric', 'gt:0'],
            'details.*.notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
