<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class ListSessionsRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'halaqa_id' => true,
        'student_id' => true,
        'state' => true,
        'from' => true,
        'to' => true,
        'page' => true,
        'per_page' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true || $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'halaqa_id' => ['sometimes', 'uuid'],
            'student_id' => ['sometimes', 'uuid'],
            'state' => ['sometimes', Rule::in(['requested', 'accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected', 'direct_connection_unavailable', 'ended', 'cancelled', 'rejected'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
