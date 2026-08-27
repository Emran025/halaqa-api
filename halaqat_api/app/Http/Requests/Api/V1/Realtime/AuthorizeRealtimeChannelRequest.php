<?php

namespace App\Http\Requests\Api\V1\Realtime;

use App\Http\Requests\StrictFormRequest;

class AuthorizeRealtimeChannelRequest extends StrictFormRequest
{
    protected array $allowedShape = ['session_id' => true, 'channel_name' => true, 'client_connection_id' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return ['session_id' => ['required', 'uuid'], 'channel_name' => ['required', 'string', 'regex:/^private-live-session\..+/'], 'client_connection_id' => ['sometimes', 'nullable', 'string', 'max:120']];
    }
}
