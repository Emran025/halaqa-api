<?php

namespace App\Http\Requests\Api\V1\Notifications;

use App\Http\Requests\StrictFormRequest;

class ListNotificationsRequest extends StrictFormRequest
{
    protected array $allowedShape = ['unread_only' => true, 'page' => true, 'per_page' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('unread_only')) {
            $this->merge(['unread_only' => filter_var($this->input('unread_only'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }
    }

    public function rules(): array
    {
        return [
            'unread_only' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
