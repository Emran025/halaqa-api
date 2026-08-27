<?php

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Validator;

class UpdateSessionReportRequest extends StrictFormRequest
{
    protected array $allowedShape = ['summary' => true];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() || $this->user()?->isStudent();
    }

    public function rules(): array
    {
        return ['summary' => ['sometimes', 'nullable', 'string', 'max:4000']];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->all() === []) {
                $validator->errors()->add('_schema', 'At least one report field is required.');
            }
        });
    }
}
