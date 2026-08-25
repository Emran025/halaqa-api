<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgressPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_read_and_update_availability_with_explicit_response(): void
    {
        $student = $this->registerStudent();
        $this->withToken($student['token'])->getJson('/api/v1/students/'.$student['user']['id'].'/availability')
            ->assertOk()->assertJsonStructure(['attendance_preferences' => ['timezone', 'preferred_session_duration_minutes', 'weekly_slots']]);

        $updated = $this->withToken($student['token'])->putJson('/api/v1/students/'.$student['user']['id'].'/availability', [
            'timezone' => 'Asia/Riyadh', 'preferred_session_duration_minutes' => 45,
            'weekly_slots' => [
                ['day_of_week' => 1, 'from' => '18:00', 'to' => '19:00', 'preferred' => true],
                ['day_of_week' => 1, 'from' => '20:00', 'to' => '21:00', 'preferred' => false],
            ],
        ]);
        $updated->assertOk()->assertJsonPath('attendance_preferences.preferred_session_duration_minutes', 45)->assertJsonCount(2, 'attendance_preferences.weekly_slots')->assertJsonMissingPath('data');
        $this->assertDatabaseCount('student_availability_slots', 2);
    }

    public function test_overlapping_availability_is_rejected_without_partial_write(): void
    {
        $student = $this->registerStudent();
        $this->withToken($student['token'])->putJson('/api/v1/students/'.$student['user']['id'].'/availability', [
            'timezone' => 'Asia/Riyadh', 'weekly_slots' => [
                ['day_of_week' => 1, 'from' => '18:00', 'to' => '20:00'],
                ['day_of_week' => 1, 'from' => '19:00', 'to' => '21:00'],
            ],
        ])->assertStatus(409)->assertJsonPath('error.code', 'availability_overlap');
        $this->assertDatabaseCount('student_availability_slots', 1);
    }

    public function test_student_can_propose_new_plan_and_read_versioned_details(): void
    {
        $student = $this->registerStudent();
        $updated = $this->withToken($student['token'])->putJson('/api/v1/students/'.$student['user']['id'].'/follow-up-plan', [
            'frequency' => 'daily', 'starts_on' => '2026-09-01', 'ends_on' => null,
            'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 2, 'notes' => 'Daily target']],
        ]);
        $updated->assertOk()->assertJsonPath('follow_up_plan.status', 'proposed')->assertJsonPath('follow_up_plan.frequency', 'daily')->assertJsonPath('follow_up_plan.version', 2)->assertJsonCount(1, 'follow_up_plan.details');

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/students/'.$student['user']['id'].'/follow-up-plan')
            ->assertOk()->assertJsonPath('follow_up_plan.details.0.task_type', 'memorization')->assertJsonMissingPath('data');
    }

    public function test_unknown_progress_fields_are_rejected(): void
    {
        $student = $this->registerStudent();
        $this->withToken($student['token'])->putJson('/api/v1/students/'.$student['user']['id'].'/availability', [
            'timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 1, 'from' => '18:00', 'to' => '19:00']], 'unexpected' => true,
        ])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }

    /** @return array<string, mixed> */
    private function registerStudent(): array
    {
        return $this->postJson('/api/v1/auth/register/student', [
            'name' => 'Progress Student', 'username' => 'progress_'.Str::lower(Str::random(6)), 'email' => 'progress_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966',
            'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 1, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => [1]],
            'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30],
            'follow_up_plan' => ['frequency' => 'twiceAWeek', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid(),
        ])->assertCreated()->json();
    }
}
