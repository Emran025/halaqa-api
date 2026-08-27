<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class RescheduleFollowUpItemRequest extends StrictFormRequest
{
    protected array $allowedShape = ['scheduled_at' => true, 'timezone' => true, 'reason' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:64', 'timezone'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }
}
