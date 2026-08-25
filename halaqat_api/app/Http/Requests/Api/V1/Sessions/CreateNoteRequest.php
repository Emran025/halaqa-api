<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class CreateNoteRequest extends StrictFormRequest
{
    protected array $allowedShape = ['body' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:1', 'max:2000'], 'client_operation_id' => ['required', 'uuid']];
    }
}
