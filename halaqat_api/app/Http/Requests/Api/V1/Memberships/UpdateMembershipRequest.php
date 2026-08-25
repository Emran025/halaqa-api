<?php

namespace App\Http\Requests\Api\V1\Memberships;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipRequest extends StrictFormRequest
{
    protected array $allowedFields = ['status', 'reason'];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'inactive', 'removed'])],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
