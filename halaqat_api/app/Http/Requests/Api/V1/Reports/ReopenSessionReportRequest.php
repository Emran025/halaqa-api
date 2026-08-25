<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\StrictFormRequest;

class ReopenSessionReportRequest extends StrictFormRequest
{
    protected array $allowedShape = ['reason' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:1', 'max:1000'], 'client_operation_id' => ['required', 'uuid']];
    }
}
