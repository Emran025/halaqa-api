<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class GetStudentProgressRequest extends StrictFormRequest
{
    protected array $allowedShape = ['task_type' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true || $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return ['task_type' => ['sometimes', Rule::in(['memorization', 'review', 'recitation'])]];
    }
}
