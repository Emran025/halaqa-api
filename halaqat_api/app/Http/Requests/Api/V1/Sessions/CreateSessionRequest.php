<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class CreateSessionRequest extends StrictFormRequest
{
    protected array $allowedShape = ['halaqa_id' => true, 'student_id' => true, 'follow_up_item_id' => true, 'task_type' => true, 'scheduled_at' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return ['halaqa_id' => ['required', 'uuid'], 'student_id' => ['required', 'uuid'], 'follow_up_item_id' => ['sometimes', 'nullable', 'uuid'], 'task_type' => ['required', Rule::in(['memorization', 'review', 'recitation'])], 'scheduled_at' => ['sometimes', 'nullable', 'date'], 'client_operation_id' => ['required', 'uuid']];
    }
}
