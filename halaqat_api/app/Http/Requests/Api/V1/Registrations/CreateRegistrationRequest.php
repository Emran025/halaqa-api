<?php

namespace App\Http\Requests\Api\V1\Registrations;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class CreateRegistrationRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'teacher_code' => true, 'requested_halaqa_id' => true, 'message' => true, 'client_operation_id' => true,
        'profile' => ['gender' => true, 'birth_date' => true, 'country' => true, 'city' => true, 'residence' => true, 'phone' => true, 'phone_zone' => true, 'whatsapp_phone' => true, 'whatsapp_zone' => true, 'memorization_level' => true, 'review_level' => true, 'bio' => true],
        'previous_memorization' => ['memorization_level' => true, 'review_level' => true, 'memorized_juz_count' => true, 'previous_teacher_notes' => true, 'stop_reasons' => true, 'memorized_surah_ids' => ['*' => []]],
        'attendance_preferences' => ['timezone' => true, 'weekly_slots' => ['*' => ['day_of_week' => true, 'from' => true, 'to' => true, 'preferred' => true]], 'preferred_session_duration_minutes' => true],
        'follow_up_plan' => ['frequency' => true, 'details' => ['*' => ['task_type' => true, 'unit' => true, 'amount' => true, 'notes' => true]], 'starts_on' => true, 'ends_on' => true],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'teacher_code' => ['sometimes', 'nullable', 'string', 'max:40', Rule::exists('teacher_profiles', 'teacher_code')],
            'requested_halaqa_id' => ['sometimes', 'nullable', 'uuid', 'exists:halaqas,id'],
            'message' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'client_operation_id' => ['required', 'uuid'],
            'profile' => ['required', 'array'],
            'profile.gender' => ['required', Rule::in(['male', 'female'])],
            'profile.birth_date' => ['required', 'date', 'before:today'],
            'profile.country' => ['required', 'string', 'max:100'],
            'profile.city' => ['required', 'string', 'max:100'],
            'profile.residence' => ['nullable', 'string', 'max:200'],
            'profile.phone' => ['required', 'string', 'max:30'],
            'profile.phone_zone' => ['required', 'string', 'max:8'],
            'profile.whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'profile.whatsapp_zone' => ['nullable', 'string', 'max:8'],
            'profile.memorization_level' => ['nullable', 'string', 'max:120'],
            'profile.review_level' => ['nullable', 'string', 'max:120'],
            'profile.bio' => ['nullable', 'string', 'max:2000'],
            'previous_memorization' => ['sometimes', 'nullable', 'array'],
            'previous_memorization.memorized_juz_count' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'previous_memorization.memorized_surah_ids' => ['nullable', 'array'],
            'previous_memorization.memorized_surah_ids.*' => ['integer', 'min:1', 'max:114'],
            'attendance_preferences' => ['required', 'array'],
            'attendance_preferences.timezone' => ['required', 'timezone:all'],
            'attendance_preferences.weekly_slots' => ['required', 'array', 'min:1'],
            'attendance_preferences.weekly_slots.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'attendance_preferences.weekly_slots.*.from' => ['required', 'date_format:H:i'],
            'attendance_preferences.weekly_slots.*.to' => ['required', 'date_format:H:i', 'after:attendance_preferences.weekly_slots.*.from'],
            'attendance_preferences.weekly_slots.*.preferred' => ['sometimes', 'boolean'],
            'attendance_preferences.preferred_session_duration_minutes' => ['sometimes', 'integer', 'between:10,180'],
            'follow_up_plan' => ['required', 'array'],
            'follow_up_plan.frequency' => ['required', Rule::in(['daily', 'onceAWeek', 'twiceAWeek', 'thriceAWeek'])],
            'follow_up_plan.details' => ['required', 'array', 'min:1'],
            'follow_up_plan.details.*.task_type' => ['required', Rule::in(['memorization', 'review', 'recitation'])],
            'follow_up_plan.details.*.unit' => ['required', Rule::in(['juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'])],
            'follow_up_plan.details.*.amount' => ['required', 'numeric', 'gt:0'],
            'follow_up_plan.details.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
