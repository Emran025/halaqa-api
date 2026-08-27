<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\StrictFormRequest;

class StoreTeacherDocumentRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true,
        'certificate_type' => true,
        'certificate_type_other' => true,
        'riwayah' => true,
        'issuing_place' => true,
        'issuing_date' => true,
        'file' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'certificate_type' => ['required', 'string', 'max:100'],
            'certificate_type_other' => ['sometimes', 'nullable', 'string', 'max:150'],
            'riwayah' => ['sometimes', 'nullable', 'string', 'max:100'],
            'issuing_place' => ['sometimes', 'nullable', 'string', 'max:200'],
            'issuing_date' => ['sometimes', 'nullable', 'date'],
            'file' => ['sometimes', 'nullable', 'file'],
        ];
    }
}
