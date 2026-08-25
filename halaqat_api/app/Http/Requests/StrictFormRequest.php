<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class StrictFormRequest extends FormRequest
{
    /** @var list<string> */
    protected array $allowedFields = [];

    /** @var array<string, mixed> */
    protected array $allowedShape = [];

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $shape = $this->allowedShape !== []
                ? $this->allowedShape
                : array_fill_keys($this->allowedFields, true);

            $this->validateShape($this->all(), $shape, '', $validator);
        });
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $shape */
    private function validateShape(array $data, array $shape, string $path, Validator $validator): void
    {
        foreach ($data as $key => $value) {
            if (! array_key_exists($key, $shape)) {
                $validator->errors()->add(
                    '_schema',
                    'Unknown field is not allowed: '.ltrim($path.'.'.$key, '.'),
                );

                continue;
            }

            $childShape = $shape[$key];
            if (! is_array($childShape)) {
                continue;
            }

            if (is_array($value) && array_key_exists('*', $childShape)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        $this->validateShape($item, $childShape['*'], $path.'.'.$key.'.'.$index, $validator);
                    }
                }
            } elseif (is_array($value)) {
                $this->validateShape($value, $childShape, ltrim($path.'.'.$key, '.'), $validator);
            }
        }
    }
}
