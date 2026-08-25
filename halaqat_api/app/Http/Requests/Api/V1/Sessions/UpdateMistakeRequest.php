<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class UpdateMistakeRequest extends StrictFormRequest
{
    protected array $allowedShape = ['mistake_type' => true, 'note' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['mistake_type' => ['sometimes', Rule::in(['none', 'memory', 'grammar', 'pronunciation', 'timing'])], 'note' => ['sometimes', 'nullable', 'string', 'max:1000']];
    }
}
