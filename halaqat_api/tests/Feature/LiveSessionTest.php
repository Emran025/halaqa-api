<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_direct_p2p_session_for_active_member(): void
    {
        $teacher = $this->registerTeacher();
        app('auth')->forgetGuards();
        $student = $this->registerStudent();
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Session Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);

        app('auth')->forgetGuards();
        $response = $this->withToken($teacher['token'])->postJson('/api/v1/sessions', ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'memorization', 'scheduled_at' => null, 'client_operation_id' => (string) Str::uuid()]);
        $response->assertCreated()->assertJsonPath('session.state', 'requested')
            ->assertJsonPath('session.direct_p2p_only', true)->assertJsonMissingPath('session.sdp')->assertJsonMissingPath('session.ice');
    }

    public function test_second_active_session_is_rejected(): void
    {
        $teacher = $this->registerTeacher();
        app('auth')->forgetGuards();
        $student = $this->registerStudent();
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Session Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);
        $payload = ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'review', 'client_operation_id' => (string) Str::uuid()];
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions', $payload)->assertCreated();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions', array_merge($payload, ['client_operation_id' => (string) Str::uuid()]))->assertStatus(409)->assertJsonPath('error.code', 'active_session_exists');
    }

    private function registerTeacher(): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => 'Session Teacher', 'username' => 'session_teacher_'.Str::lower(Str::random(6)), 'email' => 'session_teacher_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 10, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    private function registerStudent(): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => 'Session Student', 'username' => 'session_student_'.Str::lower(Str::random(6)), 'email' => 'session_student_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
