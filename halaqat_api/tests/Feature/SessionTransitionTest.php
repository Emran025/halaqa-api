<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_accept_and_teacher_can_end_session(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/accept')
            ->assertOk()->assertJsonPath('session.state', 'accepted')->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$sessionId.'/end')
            ->assertOk()->assertJsonPath('session.state', 'ended')->assertJsonMissingPath('data');
        $this->assertDatabaseHas('live_sessions', ['id' => $sessionId, 'state' => 'ended']);
    }

    public function test_student_can_reject_requested_session_and_cannot_cancel_it_as_teacher(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/reject', ['note' => 'Not available today'])
            ->assertOk()->assertJsonPath('session.state', 'rejected')->assertJsonPath('session.end_reason', 'Not available today');
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->deleteJson('/api/v1/sessions/'.$sessionId)->assertForbidden();
        $this->assertDatabaseHas('live_sessions', ['id' => $sessionId, 'state' => 'rejected', 'end_reason' => 'Not available today']);
    }

    public function test_participant_can_mark_direct_connection_unavailable_and_retry_without_duplicate_event(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();
        $operationId = (string) Str::uuid();

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/accept')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => 'Direct negotiation timed out', 'client_operation_id' => $operationId])
            ->assertOk()->assertJsonPath('session.state', 'direct_connection_unavailable');
        $this->assertDatabaseHas('live_sessions', ['id' => $sessionId, 'state' => 'direct_connection_unavailable', 'last_client_operation_id' => $operationId, 'last_operation_type' => 'direct_connection_unavailable']);
        $this->assertDatabaseCount('realtime_outbox_messages', 4);
        $this->assertDatabaseHas('realtime_outbox_messages', ['session_id' => $sessionId, 'event_type' => 'session.requested', 'recipient_id' => $student['user']['id']]);
        $this->assertDatabaseMissing('realtime_outbox_messages', ['session_id' => $sessionId, 'event_type' => 'session.requested', 'recipient_id' => $teacher['user']['id']]);

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => 'Direct negotiation timed out', 'client_operation_id' => $operationId])
            ->assertOk()->assertJsonPath('session.state', 'direct_connection_unavailable');
        $this->assertDatabaseCount('realtime_outbox_messages', 4);

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/reconnect')
            ->assertOk();
        $this->assertDatabaseHas('live_sessions', ['id' => $sessionId, 'state' => 'reconnecting']);
        $this->assertDatabaseCount('realtime_outbox_messages', 6);
    }

    public function test_direct_connection_unavailable_requires_a_session_participant_and_strict_input(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();
        $unrelatedTeacher = $this->registerTeacher('unrelated_direct_teacher');
        $unrelatedStudent = $this->registerStudent('unrelated_direct_student');

        app('auth')->forgetGuards();
        $this->withToken($unrelatedTeacher['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => 'No route', 'client_operation_id' => (string) Str::uuid()])->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($unrelatedStudent['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => 'No route', 'client_operation_id' => (string) Str::uuid()])->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => ''])->assertUnprocessable();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/direct-connection-unavailable', ['reason' => 'No route', 'client_operation_id' => (string) Str::uuid(), 'unexpected' => true])
            ->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }

    public function test_unrelated_user_cannot_view_or_accept_session(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();
        $unrelatedTeacher = $this->registerTeacher('unrelated_teacher');
        $unrelatedStudent = $this->registerStudent('unrelated_student');

        app('auth')->forgetGuards();
        $this->withToken($unrelatedTeacher['token'])->getJson('/api/v1/sessions/'.$sessionId)->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($unrelatedStudent['token'])->postJson('/api/v1/sessions/'.$sessionId.'/accept')->assertForbidden();
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: string} */
    private function createSession(): array
    {
        $teacher = $this->registerTeacher('session_teacher');
        app('auth')->forgetGuards();
        $student = $this->registerStudent('session_student');
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Transition Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);
        app('auth')->forgetGuards();
        $response = $this->withToken($teacher['token'])->postJson('/api/v1/sessions', ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'memorization', 'scheduled_at' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated();

        return [$teacher, $student, $response->json('session.id')];
    }

    /** @return array<string,mixed> */
    private function registerTeacher(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => 'Transition Teacher', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 10, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    /** @return array<string,mixed> */
    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => 'Transition Student', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
