<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class ListFollowUpItemsRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'date' => true,
        'state' => true,
        'task_type' => true,
        'student_id' => true,
        'page' => true,
        'per_page' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'state' => ['sometimes', 'in:upcoming,due,in_progress,completed,skipped,overdue'],
            'task_type' => ['sometimes', 'in:memorization,review,recitation'],
            'student_id' => ['sometimes', 'uuid'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
