<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class ListNotesRequest extends StrictFormRequest
{
    protected array $allowedShape = ['per_page' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['per_page' => ['sometimes', 'integer', 'between:1,100']];
    }
}
