<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class CompleteFollowUpItemRequest extends StrictFormRequest
{
    protected array $allowedShape = ['client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return ['client_operation_id' => ['required', 'uuid']];
    }
}
