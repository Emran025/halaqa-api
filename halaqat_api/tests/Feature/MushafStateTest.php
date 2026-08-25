<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MushafStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_save_and_retry_official_mushaf_state(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();
        $this->seed(QuranReferenceSeeder::class);
        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$sessionId.'/mushaf-state')->assertNotFound();
        $payload = ['edition_id' => 1, 'page_number' => 1, 'surah_id' => 1, 'ayah_id' => 1, 'range' => ['edition_id' => 1, 'start_page' => 1, 'start_ayah_id' => 1, 'end_page' => 1, 'end_ayah_id' => 7, 'end_ayah_number' => 7], 'client_operation_id' => (string) Str::uuid()];

        $saved = $this->withToken($teacher['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', $payload)
            ->assertOk()->assertJsonPath('mushaf_state.edition_id', 1)->assertJsonPath('mushaf_state.page_number', 1)->assertJsonPath('mushaf_state.range.end_ayah_number', 7)->assertJsonPath('mushaf_state.version', 1)->assertJsonMissingPath('data');
        $this->withToken($teacher['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', $payload)
            ->assertOk()->assertJsonPath('mushaf_state.version', 1)->assertJsonPath('mushaf_state.updated_by', $teacher['user']['id']);
        app('auth')->forgetGuards();
        $nextPayload = ['edition_id' => 1, 'page_number' => 2, 'surah_id' => 1, 'ayah_id' => 8, 'range' => null, 'client_operation_id' => (string) Str::uuid()];
        $this->withToken($student['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', $nextPayload)
            ->assertOk()->assertJsonPath('mushaf_state.page_number', 2)->assertJsonPath('mushaf_state.ayah_id', 8)->assertJsonPath('mushaf_state.version', 2)->assertJsonPath('mushaf_state.updated_by', $student['user']['id']);
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$sessionId.'/mushaf-state')
            ->assertOk()->assertJsonPath('mushaf_state.version', 2)->assertJsonPath('mushaf_state.range', null);
        $this->assertDatabaseHas('session_mushaf_states', ['session_id' => $sessionId, 'version' => 2, 'last_client_operation_id' => $nextPayload['client_operation_id']]);
        $this->assertNotNull($saved->json('mushaf_state.updated_at'));
    }

    public function test_mushaf_state_rejects_invalid_coordinates_and_unrelated_users(): void
    {
        [$teacher, $student, $sessionId] = $this->createSession();
        $this->seed(QuranReferenceSeeder::class);
        $basePayload = ['edition_id' => 1, 'page_number' => 1, 'surah_id' => 1, 'ayah_id' => 1, 'client_operation_id' => (string) Str::uuid()];

        $this->withToken($teacher['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', $basePayload)
            ->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', ['edition_id' => 1, 'page_number' => 2, 'surah_id' => 1, 'ayah_id' => 1, 'client_operation_id' => (string) Str::uuid()])
            ->assertStatus(409)->assertJsonPath('error.code', 'quran_ayah_mismatch');
        $unrelated = $this->registerStudent('mushaf_unrelated');
        app('auth')->forgetGuards();
        $this->withToken($unrelated['token'])->getJson('/api/v1/sessions/'.$sessionId.'/mushaf-state')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($unrelated['token'])->putJson('/api/v1/sessions/'.$sessionId.'/mushaf-state', $basePayload)->assertForbidden();
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: string} */
    private function createSession(): array
    {
        $teacher = $this->registerTeacher('mushaf_teacher');
        app('auth')->forgetGuards();
        $student = $this->registerStudent('mushaf_student');
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Mushaf Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'timezone' => 'Asia/Riyadh', 'max_students' => 5, 'status' => 'active']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);
        app('auth')->forgetGuards();
        $session = $this->withToken($teacher['token'])->postJson('/api/v1/sessions', ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'memorization', 'client_operation_id' => (string) Str::uuid()])->assertCreated();

        return [$teacher, $student, $session->json('session.id')];
    }

    /** @return array<string,mixed> */
    private function registerTeacher(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => 'Mushaf Teacher', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 10, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    /** @return array<string,mixed> */
    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => 'Mushaf Student', 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
