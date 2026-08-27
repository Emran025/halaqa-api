<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\StrictFormRequest;

class ListStudentReportsRequest extends StrictFormRequest
{
    protected array $allowedShape = ['task_type' => true, 'from' => true, 'to' => true, 'page' => true, 'per_page' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return [
            'task_type' => ['sometimes', 'in:memorization,review,recitation'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
