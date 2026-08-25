<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends StrictFormRequest
{
    protected array $allowedShape = ['planned_from_unit_id' => true, 'planned_to_unit_id' => true, 'start_page' => true, 'start_ayah_id' => true, 'end_page' => true, 'end_ayah_id' => true, 'current_page' => true, 'current_ayah_id' => true, 'state' => true, 'planned_amount' => true, 'actual_amount' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['planned_from_unit_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'planned_to_unit_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'start_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'start_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236'], 'end_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'end_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236'], 'current_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'current_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236'], 'state' => ['sometimes', Rule::in(['draft', 'in_progress', 'completed', 'skipped', 'cancelled'])], 'planned_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'], 'actual_amount' => ['sometimes', 'nullable', 'numeric', 'min:0']];
    }
}
