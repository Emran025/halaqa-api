<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Database\Seeders\MistakeTypeSeeder;
use Database\Seeders\QuranReferenceSeeder;
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
        $sessionPayload = ['halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'task_type' => 'memorization', 'scheduled_at' => null, 'client_operation_id' => (string) Str::uuid()];
        $response = $this->withToken($teacher['token'])->postJson('/api/v1/sessions', $sessionPayload);
        $response->assertCreated()->assertJsonPath('session.state', 'requested')
            ->assertJsonPath('session.direct_p2p_only', true)->assertJsonMissingPath('session.sdp')->assertJsonMissingPath('session.ice');
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions', $sessionPayload)
            ->assertCreated()->assertJsonPath('session.id', $response->json('session.id'));
        $this->assertDatabaseCount('live_sessions', 1);

        $taskPayload = ['task_type' => 'memorization', 'planned_amount' => 2, 'sequence_no' => 1, 'client_operation_id' => (string) Str::uuid()];
        $task = $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$response->json('session.id').'/tasks', $taskPayload)
            ->assertCreated()->assertJsonPath('task.task_type', 'memorization')->assertJsonPath('task.state', 'draft')->assertJsonPath('task.sequence_no', 1);

        $taskId = $task->json('task.id');
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$response->json('session.id').'/tasks', $taskPayload)
            ->assertCreated()->assertJsonPath('task.id', $taskId);
        $this->assertDatabaseCount('session_tasks', 1);
        $this->assertDatabaseCount('tracking_details', 1);
        $sessionId = $response->json('session.id');
        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$sessionId.'/tasks')
            ->assertOk()->assertJsonCount(1, 'tasks')->assertJsonPath('tasks.0.id', $taskId)->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId)
            ->assertOk()->assertJsonPath('task.id', $taskId)->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->patchJson('/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId, ['current_page' => 2, 'current_ayah_id' => 8])
            ->assertOk()->assertJsonPath('task.id', $taskId);
        $this->assertDatabaseHas('session_tasks', ['id' => $taskId, 'current_page' => 2, 'current_ayah_id' => 8]);
        $draftPayload = ['client_operation_id' => (string) Str::uuid(), 'current_page' => 3, 'current_ayah_id' => 15];
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId.'/save-draft', $draftPayload)
            ->assertOk()->assertJsonPath('task.id', $taskId);
        $this->withToken($student['token'])->postJson('/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId.'/save-draft', $draftPayload)
            ->assertOk()->assertJsonPath('task.id', $taskId);
        $this->assertDatabaseHas('session_tasks', ['id' => $taskId, 'current_page' => 3, 'current_ayah_id' => 15, 'last_draft_operation_id' => $draftPayload['client_operation_id']]);
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->patchJson('/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId, ['state' => 'in_progress', 'planned_amount' => 2])
            ->assertOk()->assertJsonPath('task.state', 'in_progress');
        $this->assertDatabaseMissing('session_tasks', ['id' => $taskId, 'started_at' => null]);
        $base = '/api/v1/sessions/'.$sessionId.'/tasks/'.$taskId;
        $notePayload = ['body' => 'Read carefully', 'client_operation_id' => (string) Str::uuid()];
        $createdNote = $this->withToken($teacher['token'])->postJson($base.'/notes', $notePayload)
            ->assertCreated()->assertJsonPath('note.body', 'Read carefully')->assertJsonMissingPath('data');
        $this->withToken($teacher['token'])->postJson($base.'/notes', $notePayload)
            ->assertCreated()->assertJsonPath('note.id', $createdNote->json('note.id'));
        $this->assertDatabaseCount('task_notes', 1);
        $this->withToken($teacher['token'])->putJson($base.'/evaluation', ['score' => 95, 'comment' => 'Excellent'])
            ->assertOk()->assertJsonPath('teacher.score', 95)->assertJsonPath('teacher.evaluator_role', 'teacher')->assertJsonPath('student', null)->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson($base.'/evaluation')
            ->assertOk()->assertJsonPath('teacher.score', 95)->assertJsonPath('student', null)->assertJsonMissingPath('data');
        $notes = $this->withToken($student['token'])->getJson($base.'/notes')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonCount(1, 'notes')->assertJsonMissingPath('data');
        $teacherNoteId = $notes->json('notes.0.id');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->patchJson($base.'/notes/'.$teacherNoteId, ['body' => 'Student cannot edit teacher note'])
            ->assertStatus(409)->assertJsonPath('error.code', 'note_not_owned');
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->deleteJson($base.'/notes/'.$teacherNoteId)->assertNoContent();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson($base.'/notes')->assertOk()->assertJsonPath('meta.total', 0);
        $this->seed([MistakeTypeSeeder::class, QuranReferenceSeeder::class]);
        app('auth')->forgetGuards();
        $mistakePayload = ['ayah_id' => 1, 'page_number' => 1, 'word_index' => 1, 'mistake_type' => 'pronunciation', 'note' => 'Articulation', 'client_operation_id' => (string) Str::uuid()];
        $mistake = $this->withToken($teacher['token'])->postJson($base.'/mistakes', $mistakePayload)
            ->assertCreated()->assertJsonPath('mistake.ayah_id', 1)->assertJsonPath('mistake.page_number', 1)->assertJsonPath('mistake.mistake_type', 'pronunciation')->assertJsonPath('mistake.source', 'teacher')->assertJsonMissingPath('data');
        $mistakeId = $mistake->json('mistake.id');
        $this->withToken($teacher['token'])->postJson($base.'/mistakes', $mistakePayload)
            ->assertCreated()->assertJsonPath('mistake.id', $mistakeId);
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson($base.'/mistakes')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonCount(1, 'mistakes')->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->patchJson($base.'/mistakes/'.$mistakeId, ['note' => 'Student correction'])
            ->assertOk()->assertJsonPath('mistake.note', 'Student correction');
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->deleteJson($base.'/mistakes/'.$mistakeId)->assertNoContent();
        $this->assertSoftDeleted('mistakes', ['id' => $mistakeId]);
        $this->withToken($teacher['token'])->getJson($base.'/mistakes')->assertOk()->assertJsonPath('meta.total', 0);
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
