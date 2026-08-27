<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Validator;

class StudentAcknowledgmentReportRequest extends StrictFormRequest
{
    protected array $allowedShape = ['action' => true, 'note' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:acknowledge,comment'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->input('action') === 'comment' && trim((string) $this->input('note')) === '') {
                $validator->errors()->add('note', 'A note is required when action is comment.');
            }
        });
    }
}
