<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class SkipFollowUpItemRequest extends StrictFormRequest
{
    protected array $allowedShape = ['reason' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:1', 'max:500'], 'client_operation_id' => ['required', 'uuid']];
    }
}
