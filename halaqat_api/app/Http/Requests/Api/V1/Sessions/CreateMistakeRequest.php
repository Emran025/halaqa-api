<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class CreateMistakeRequest extends StrictFormRequest
{
    protected array $allowedShape = ['ayah_id' => true, 'page_number' => true, 'word_index' => true, 'mistake_type' => true, 'note' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['ayah_id' => ['required', 'integer', 'between:1,6236'], 'page_number' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'word_index' => ['required', 'integer', 'min:1'], 'mistake_type' => ['required', Rule::in(['none', 'memory', 'grammar', 'pronunciation', 'timing'])], 'note' => ['sometimes', 'nullable', 'string', 'max:1000'], 'client_operation_id' => ['required', 'uuid']];
    }
}
