<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\StrictFormRequest;

class ApprovalReportRequest extends StrictFormRequest
{
    protected array $allowedShape = ['note' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return ['note' => ['sometimes', 'nullable', 'string', 'max:2000'], 'client_operation_id' => ['required', 'uuid']];
    }
}
