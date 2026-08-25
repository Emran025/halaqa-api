<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class MarkDirectConnectionUnavailableRequest extends StrictFormRequest
{
    protected array $allowedShape = ['reason' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isActive() === true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:1', 'max:500'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }
}
