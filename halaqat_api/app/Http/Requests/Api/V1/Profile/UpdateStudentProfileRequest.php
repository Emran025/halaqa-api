<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentProfileRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true, 'birth_date' => true, 'gender' => true, 'country' => true,
        'city' => true, 'residence' => true, 'phone' => true, 'phone_zone' => true,
        'whatsapp_phone' => true, 'whatsapp_zone' => true, 'memorization_level' => true,
        'review_level' => true, 'previous_memorization' => [
            'memorization_level' => true, 'review_level' => true, 'memorized_juz_count' => true,
            'memorized_surah_ids' => ['*' => []], 'last_completed_unit' => [
                'task_type' => true, 'unit' => true, 'amount' => true, 'notes' => true,
            ], 'previous_teacher_notes' => true, 'stop_reasons' => true,
        ], 'attendance_preferences' => [
            'timezone' => true, 'weekly_slots' => ['*' => [
                'day_of_week' => true, 'from' => true, 'to' => true, 'preferred' => true,
            ]], 'preferred_session_duration_minutes' => true,
        ], 'follow_up_plan' => [
            'frequency' => true, 'details' => ['*' => [
                'task_type' => true, 'unit' => true, 'amount' => true, 'notes' => true,
            ]], 'starts_on' => true, 'ends_on' => true,
        ], 'bio' => true,
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStudent() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:120'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'residence' => ['sometimes', 'nullable', 'string', 'max:200'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_zone' => ['sometimes', 'nullable', 'string', 'max:8'],
            'whatsapp_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp_zone' => ['sometimes', 'nullable', 'string', 'max:8'],
            'memorization_level' => ['sometimes', 'nullable', 'string', 'max:120'],
            'review_level' => ['sometimes', 'nullable', 'string', 'max:120'],
            'previous_memorization' => ['sometimes', 'nullable', 'array'],
            'previous_memorization.memorization_level' => ['sometimes', 'nullable', 'string', 'max:120'],
            'previous_memorization.review_level' => ['sometimes', 'nullable', 'string', 'max:120'],
            'previous_memorization.memorized_juz_count' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:30'],
            'previous_memorization.memorized_surah_ids' => ['sometimes', 'nullable', 'array'],
            'previous_memorization.memorized_surah_ids.*' => ['integer', 'min:1', 'max:114'],
            'previous_memorization.last_completed_unit' => ['sometimes', 'nullable', 'array'],
            'previous_memorization.last_completed_unit.task_type' => ['required_with:previous_memorization.last_completed_unit', Rule::in(['memorization', 'review', 'recitation'])],
            'previous_memorization.last_completed_unit.unit' => ['required_with:previous_memorization.last_completed_unit', Rule::in(['juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'])],
            'previous_memorization.last_completed_unit.amount' => ['required_with:previous_memorization.last_completed_unit', 'numeric', 'gt:0'],
            'previous_memorization.last_completed_unit.notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'previous_memorization.previous_teacher_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'previous_memorization.stop_reasons' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'attendance_preferences' => ['sometimes', 'array'],
            'attendance_preferences.timezone' => ['required_with:attendance_preferences', 'timezone:all'],
            'attendance_preferences.weekly_slots' => ['required_with:attendance_preferences', 'array', 'min:1'],
            'attendance_preferences.weekly_slots.*.day_of_week' => ['required_with:attendance_preferences.weekly_slots', 'integer', 'between:0,6'],
            'attendance_preferences.weekly_slots.*.from' => ['required_with:attendance_preferences.weekly_slots', 'date_format:H:i'],
            'attendance_preferences.weekly_slots.*.to' => ['required_with:attendance_preferences.weekly_slots', 'date_format:H:i', 'after:attendance_preferences.weekly_slots.*.from'],
            'attendance_preferences.weekly_slots.*.preferred' => ['sometimes', 'boolean'],
            'attendance_preferences.preferred_session_duration_minutes' => ['sometimes', 'integer', 'between:10,180'],
            'follow_up_plan' => ['sometimes', 'array'],
            'follow_up_plan.frequency' => ['required_with:follow_up_plan', Rule::in(['daily', 'onceAWeek', 'twiceAWeek', 'thriceAWeek'])],
            'follow_up_plan.details' => ['required_with:follow_up_plan', 'array', 'min:1'],
            'follow_up_plan.details.*.task_type' => ['required_with:follow_up_plan.details', Rule::in(['memorization', 'review', 'recitation'])],
            'follow_up_plan.details.*.unit' => ['required_with:follow_up_plan.details', Rule::in(['juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'])],
            'follow_up_plan.details.*.amount' => ['required_with:follow_up_plan.details', 'numeric', 'gt:0'],
            'follow_up_plan.details.*.notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'follow_up_plan.starts_on' => ['sometimes', 'nullable', 'date'],
            'follow_up_plan.ends_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:follow_up_plan.starts_on'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->isMethod('PATCH') && $this->all() === []) {
                $validator->errors()->add('_schema', 'At least one field is required.');
            }
        });
    }
}
