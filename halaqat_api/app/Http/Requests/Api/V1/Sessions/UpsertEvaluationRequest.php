<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class UpsertEvaluationRequest extends StrictFormRequest
{
    protected array $allowedShape = ['score' => true, 'comment' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['score' => ['required', 'numeric', 'between:0,100'], 'comment' => ['sometimes', 'nullable', 'string', 'max:2000']];
    }
}
