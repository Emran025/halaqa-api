<?php

namespace App\Http\Requests\Api\V1\Progress;

use App\Http\Requests\StrictFormRequest;

class ListTrackingsRequest extends StrictFormRequest
{
    protected array $allowedShape = ['from' => true, 'to' => true, 'page' => true, 'per_page' => true];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true || $this->user()?->isTeacher() === true;
    }

    public function rules(): array
    {
        return ['from' => ['sometimes', 'date'], 'to' => ['sometimes', 'date', 'after_or_equal:from'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,100']];
    }
}
