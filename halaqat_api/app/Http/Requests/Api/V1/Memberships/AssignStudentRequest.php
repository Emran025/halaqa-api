<?php

namespace App\Http\Requests\Api\V1\Memberships;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class AssignStudentRequest extends StrictFormRequest
{
    protected array $allowedFields = ['student_id'];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'student')->where('status', 'active'))],
        ];
    }
}
