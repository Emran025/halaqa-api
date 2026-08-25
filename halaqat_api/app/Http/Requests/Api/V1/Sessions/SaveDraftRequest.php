<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class SaveDraftRequest extends StrictFormRequest
{
    protected array $allowedShape = ['client_operation_id' => true, 'current_page' => true, 'current_ayah_id' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['client_operation_id' => ['required', 'uuid'], 'current_page' => ['sometimes', 'nullable', 'integer', 'between:1,604'], 'current_ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236']];
    }
}
