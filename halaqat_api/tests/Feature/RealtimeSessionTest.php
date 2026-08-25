<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_receive_laravel_only_realtime_config_and_can_reconnect(): void
    {
        [$teacher, $student, $sessionId] = $this->createAcceptedSession();

        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$sessionId.'/realtime')
            ->assertOk()->assertJsonPath('realtime_session.session_id', $sessionId)->assertJsonPath('realtime_session.channel_name', 'private-live-session.'.$sessionId)->assertJsonPath('realtime_session.signaling_transport', 'laravel_websocket')->assertJsonPath('realtime_session.direct_p2p_only', true)->assertJsonPath('realtime_session.ice_candidate_policy', 'host_only')->assertJsonPath('realtime_session.media_transport', 'webrtc_peer_to_peer')->assertJsonMissingPath('data');
        app('auth')->forgetGuards();

        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/reconnect')
            ->assertOk()->assertJsonPath('realtime_session.session_id', $sessionId)->assertJsonPath('realtime_session.direct_p2p_only', true);
        $this->assertDatabaseHas('live_sessions', ['id' => $sessionId, 'state' => 'reconnecting']);
    }

    public function test_channel_authorization_requires_session_participant_and_exact_private_channel(): void
    {
        [$teacher, $student, $sessionId] = $this->createAcceptedSession();
        app('auth')->forgetGuards();
        $channel = 'private-live-session.'.$sessionId;
        $payload = ['session_id' => $sessionId, 'channel_name' => $channel, 'client_connection_id' => 'wpf-connection-1'];

        $this->withToken($teacher['token'])->postJson('/api/v1/realtime/channels/authorize', $payload)
            ->assertOk()->assertJsonPath('authorization.authorized', true)->assertJsonPath('authorization.channel_name', $channel)->assertJsonPath('authorization.recipient_id', $student['user']['id'])->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $unrelated = $this->registerStudent('realtime_unrelated');
        $this->withToken($unrelated['token'])->postJson('/api/v1/realtime/channels/authorize', $payload)->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/realtime/channels/authorize', ['session_id' => $sessionId, 'channel_name' => 'private-live-session.wrong'])->assertStatus(409)->assertJsonPath('error.code', 'realtime_channel_mismatch');
    }

    public function test_requested_session_cannot_start_realtime_or_reconnect(): void
    {
        [$teacher, $student, $sessionId] = $this->createRequestedSession();

        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$sessionId.'/realtime')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$sessionId.'/reconnect')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/reconnect')->assertForbidden();
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: string} */
    private function createAcceptedSession(): array
    {
        [$teacher, $student, $sessionId] = $this->createRequestedSession();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/accept')->assertOk();

        return [$teacher, $student, $sessionId];
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: string} */
    private function createRequestedSession(): array
    {
        $teacher = $this->registerTeacher('realtime_teacher');
        app('auth')->forgetGuards();
        $student = $this->registerStudent('realtime_student');
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Realtime Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);
        app('auth')->forgetGuards();
        $session = $this->withToken($teacher['token'])->postJson('/api/v1/sessions', ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'memorization', 'client_operation_id' => (string) Str::uuid()])->assertCreated();

        return [$teacher, $student, $session->json('session.id')];
    }

    /** @return array<string,mixed> */
    private function registerTeacher(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => 'Realtime Teacher', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 10, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    /** @return array<string,mixed> */
    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => 'Realtime Student', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
