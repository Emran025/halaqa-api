<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends StrictFormRequest
{
    protected array $allowedShape = ['task_type' => true, 'client_operation_id' => true, 'sequence_no' => true, 'planned_amount' => true, 'planned_from_unit_id' => true, 'planned_to_unit_id' => true, 'start_page' => true, 'start_ayah_id' => true, 'end_page' => true, 'end_ayah_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return ['task_type' => ['required', Rule::in(['memorization', 'review', 'recitation'])], 'client_operation_id' => ['required', 'uuid'], 'sequence_no' => ['sometimes', 'integer', 'min:1'], 'planned_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'], 'planned_from_unit_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'planned_to_unit_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'start_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'start_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236'], 'end_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'end_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236']];
    }
}
