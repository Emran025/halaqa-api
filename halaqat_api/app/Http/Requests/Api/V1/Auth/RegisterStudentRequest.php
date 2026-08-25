<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\StrictFormRequest;
use Illuminate\Validation\Rule;

class RegisterStudentRequest extends StrictFormRequest
{
    protected array $allowedShape = [
        'name' => true, 'username' => true, 'email' => true, 'password' => true,
        'password_confirmation' => true, 'gender' => true, 'birth_date' => true,
        'country' => true, 'city' => true, 'residence' => true, 'phone' => true,
        'phone_zone' => true, 'whatsapp_phone' => true, 'whatsapp_zone' => true,
        'memorization_level' => true, 'review_level' => true, 'previous_memorization' => [
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
        ], 'teacher_code' => true, 'profile_bio' => true, 'client_operation_id' => true,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'username' => ['nullable', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
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
            'memorization_level' => ['nullable', 'string', 'max:120'],
            'review_level' => ['nullable', 'string', 'max:120'],
            'previous_memorization' => ['nullable', 'array'],
            'previous_memorization.memorization_level' => ['nullable', 'string', 'max:120'],
            'previous_memorization.review_level' => ['nullable', 'string', 'max:120'],
            'previous_memorization.memorized_juz_count' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'previous_memorization.memorized_surah_ids' => ['nullable', 'array'],
            'previous_memorization.memorized_surah_ids.*' => ['integer', 'min:1', 'max:114'],
            'previous_memorization.last_completed_unit' => ['nullable', 'array'],
            'previous_memorization.last_completed_unit.task_type' => ['required_with:previous_memorization.last_completed_unit', Rule::in(['memorization', 'review', 'recitation'])],
            'previous_memorization.last_completed_unit.unit' => ['required_with:previous_memorization.last_completed_unit', Rule::in(['juz', 'hizb', 'halfHizb', 'quarterHizb', 'page'])],
            'previous_memorization.last_completed_unit.amount' => ['required_with:previous_memorization.last_completed_unit', 'numeric', 'gt:0'],
            'previous_memorization.last_completed_unit.notes' => ['nullable', 'string', 'max:500'],
            'previous_memorization.previous_teacher_notes' => ['nullable', 'string', 'max:2000'],
            'previous_memorization.stop_reasons' => ['nullable', 'string', 'max:2000'],
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
            'follow_up_plan.starts_on' => ['nullable', 'date'],
            'follow_up_plan.ends_on' => ['nullable', 'date', 'after_or_equal:follow_up_plan.starts_on'],
            'teacher_code' => ['nullable', 'string', 'max:40', Rule::exists('teacher_profiles', 'teacher_code')],
            'profile_bio' => ['nullable', 'string', 'max:2000'],
            'client_operation_id' => ['required', 'uuid'],
        ];
    }
}
