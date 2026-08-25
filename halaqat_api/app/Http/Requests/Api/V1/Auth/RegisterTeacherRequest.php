<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\StrictFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class RegisterTeacherRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true, 'username' => true, 'email' => true, 'password' => true,
        'password_confirmation' => true, 'gender' => true, 'birth_date' => true,
        'country' => true, 'city' => true, 'residence' => true, 'phone' => true,
        'phone_zone' => true, 'whatsapp_phone' => true, 'whatsapp_zone' => true,
        'qualification' => true, 'experience_years' => true, 'bio' => true,
        'available_time' => true, 'documents' => ['*' => [
            'name' => true, 'certificate_type' => true, 'certificate_type_other' => true,
            'riwayah' => true, 'issuing_place' => true, 'issuing_date' => true, 'file_url' => true,
        ]], 'max_halaqas' => true, 'client_operation_id' => true,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $existing = User::query()->where('client_operation_id', $this->input('client_operation_id'))->first();

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'username' => ['nullable', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')->ignore($existing?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing?->id)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'residence' => ['nullable', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_zone' => ['required', 'string', 'max:8'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_zone' => ['nullable', 'string', 'max:8'],
            'qualification' => ['required', 'string', 'max:250'],
            'experience_years' => ['required', 'integer', 'between:0,80'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'available_time' => ['nullable', 'date_format:H:i'],
            'documents' => ['sometimes', 'array'],
            'documents.*.name' => ['required', 'string', 'max:250'],
            'documents.*.certificate_type' => ['required', 'string', 'max:100'],
            'documents.*.certificate_type_other' => ['nullable', 'string', 'max:150'],
            'documents.*.riwayah' => ['nullable', 'string', 'max:100'],
            'documents.*.issuing_place' => ['nullable', 'string', 'max:200'],
            'documents.*.issuing_date' => ['nullable', 'date'],
            'documents.*.file_url' => ['nullable', 'url', 'max:500'],
            'max_halaqas' => ['sometimes', 'integer', 'min:0'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }
}
