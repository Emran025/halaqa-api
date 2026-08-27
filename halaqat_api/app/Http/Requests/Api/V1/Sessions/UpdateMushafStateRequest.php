<?php

namespace App\Http\Requests\Api\V1\Sessions;

use App\Http\Requests\StrictFormRequest;

class UpdateMushafStateRequest extends StrictFormRequest
{
    protected array $allowedShape = ['edition_id' => true, 'page_number' => true, 'surah_id' => true, 'ayah_id' => true, 'range' => true, 'client_operation_id' => true];

    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'student'], true);
    }

    public function rules(): array
    {
        return [
            'edition_id' => ['required', 'integer', 'min:1'],
            'page_number' => ['required', 'integer', 'between:1,604'],
            'surah_id' => ['sometimes', 'nullable', 'integer', 'between:1,114'],
            'ayah_id' => ['sometimes', 'nullable', 'integer', 'between:1,6236'],
            'range' => ['sometimes', 'nullable', 'array:edition_id,start_page,start_ayah_id,end_page,end_ayah_id,end_ayah_number'],
            'range.edition_id' => ['required_with:range', 'integer', 'min:1'],
            'range.start_page' => ['required_with:range', 'nullable', 'integer', 'between:1,604'],
            'range.start_ayah_id' => ['required_with:range', 'nullable', 'integer', 'between:1,6236'],
            'range.end_page' => ['required_with:range', 'nullable', 'integer', 'between:1,604'],
            'range.end_ayah_id' => ['required_with:range', 'nullable', 'integer', 'between:1,6236'],
            'range.end_ayah_number' => ['nullable', 'integer', 'between:1,6236'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }
}
