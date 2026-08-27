<?php

namespace Tests\Feature;

use App\Models\DailyTracking;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\SessionReport;
use App\Models\SessionTask;
use App\Models\TrackingDetail;
use Database\Seeders\MistakeTypeSeeder;
use Database\Seeders\QuranReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ending_a_session_creates_a_report_and_participants_can_read_it(): void
    {
        [$teacher, $student, $session] = $this->sessionFixture();

        $this->withToken($student['token'])->getJson('/api/v1/sessions/'.$session->id.'/report')->assertNotFound();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$session->id.'/end', ['reason' => 'completed'])
            ->assertOk()->assertJsonPath('session.state', 'ended');
        $this->assertDatabaseHas('session_reports', ['session_id' => $session->id, 'state' => 'draft', 'total_tasks' => 0]);
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/sessions/'.$session->id.'/report')
            ->assertOk()->assertJsonPath('report.session_id', $session->id)
            ->assertJsonPath('report.state', 'draft')
            ->assertJsonPath('report.teacher_approval.status', 'pending')
            ->assertJsonPath('report.student_acknowledgment.status', 'pending')
            ->assertJsonMissingPath('data');
    }

    public function test_report_metrics_include_tasks_and_active_mistakes(): void
    {
        $this->seed([MistakeTypeSeeder::class, QuranReferenceSeeder::class]);
        [$teacher, $student, $session] = $this->sessionFixture();
        $membership = HalaqaMembership::query()->where('student_id', $student['user']['id'])->firstOrFail();
        $tracking = DailyTracking::create(['id' => (string) Str::uuid(), 'membership_id' => $membership->id, 'student_id' => $student['user']['id'], 'date' => '2026-09-02', 'attendance_type' => 'present']);
        $task = SessionTask::create(['id' => (string) Str::uuid(), 'session_id' => $session->id, 'client_operation_id' => (string) Str::uuid(), 'tracking_type_id' => 1, 'sequence_no' => 1, 'planned_amount' => 1, 'actual_amount' => 1, 'state' => 'completed', 'started_at' => now()->subMinutes(30), 'completed_at' => now()->subMinutes(5)]);
        $detail = TrackingDetail::create(['uuid' => (string) Str::uuid(), 'tracking_id' => $tracking->id, 'session_task_id' => $task->id, 'tracking_type_id' => 1, 'actual_amount' => 1, 'state' => 'completed']);
        Mistake::create(['id' => (string) Str::uuid(), 'tracking_detail_id' => $detail->uuid, 'ayah_id' => 1, 'edition_id' => 1, 'word_index' => 1, 'mistake_type_id' => 4, 'source_role' => 'teacher', 'note' => 'تصحيح النطق', 'created_by_user_id' => $teacher['user']['id'], 'client_operation_id' => (string) Str::uuid()]);

        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$session->id.'/end')->assertOk();
        $this->withToken($teacher['token'])->getJson('/api/v1/sessions/'.$session->id.'/report')
            ->assertOk()->assertJsonPath('report.total_tasks', 1)->assertJsonPath('report.total_mistakes', 1)->assertJsonPath('report.mistake_counts.0.mistake_type', 'pronunciation')->assertJsonPath('report.mistake_counts.0.count', 1)->assertJsonPath('report.tasks.0.mistakes_count', 1);
    }

    public function test_report_lifecycle_is_atomic_and_idempotent(): void
    {
        [$teacher, $student, $session] = $this->sessionFixture();
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$session->id.'/end')->assertOk();
        $base = '/api/v1/sessions/'.$session->id.'/report';

        $this->withToken($teacher['token'])->patchJson($base, ['summary' => 'جلسة جيدة'])
            ->assertOk()->assertJsonPath('report.summary', 'جلسة جيدة')->assertJsonPath('report.version', 2);
        $approvalOperation = (string) Str::uuid();
        $approval = $this->withToken($teacher['token'])->postJson($base.'/teacher-approval', ['note' => 'تمت المراجعة', 'client_operation_id' => $approvalOperation])
            ->assertOk()->assertJsonPath('report.state', 'pending_student_acknowledgment');
        $this->withToken($teacher['token'])->postJson($base.'/teacher-approval', ['note' => 'تمت المراجعة', 'client_operation_id' => $approvalOperation])
            ->assertOk()->assertJsonPath('report.id', $approval->json('report.id'));
        $this->withToken($teacher['token'])->postJson($base.'/teacher-approval', ['client_operation_id' => (string) Str::uuid()])
            ->assertStatus(409)->assertJsonPath('error.code', 'report_state_conflict');

        $commentOperation = (string) Str::uuid();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson($base.'/student-acknowledgment', ['action' => 'comment', 'note' => 'لدي ملاحظة', 'client_operation_id' => $commentOperation])
            ->assertOk()->assertJsonPath('report.state', 'pending_student_acknowledgment')->assertJsonPath('report.student_acknowledgment.status', 'comment_submitted');
        $ackOperation = (string) Str::uuid();
        $this->withToken($student['token'])->postJson($base.'/student-acknowledgment', ['action' => 'acknowledge', 'client_operation_id' => $ackOperation])
            ->assertOk()->assertJsonPath('report.state', 'completed')->assertJsonPath('report.student_acknowledgment.note', 'لدي ملاحظة');
        $reopenOperation = (string) Str::uuid();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson($base.'/reopen', ['reason' => 'تصحيح ملحوظة', 'client_operation_id' => $reopenOperation])
            ->assertOk()->assertJsonPath('report.state', 'reopened')->assertJsonPath('report.reopen_reason', 'تصحيح ملحوظة');
        $this->assertDatabaseHas('session_reports', ['session_id' => $session->id, 'state' => 'reopened', 'last_client_operation_id' => $reopenOperation]);
    }

    public function test_report_permissions_and_strict_inputs_are_enforced(): void
    {
        [$teacher, $student, $session] = $this->sessionFixture();
        $otherTeacher = $this->registerTeacher('report_other_teacher');
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$session->id.'/end')->assertOk();
        $base = '/api/v1/sessions/'.$session->id.'/report';

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->patchJson($base, ['summary' => 'غير مسموح'])->assertForbidden();
        $this->withToken($student['token'])->postJson($base.'/teacher-approval', ['client_operation_id' => (string) Str::uuid()])->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson($base.'/student-acknowledgment', ['action' => 'comment', 'client_operation_id' => (string) Str::uuid()])->assertForbidden();
        $this->withToken($teacher['token'])->patchJson($base, [])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
        $this->withToken($teacher['token'])->patchJson($base, ['summary' => 'صحيح', 'unexpected' => true])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
        app('auth')->forgetGuards();
        $this->withToken($otherTeacher['token'])->getJson($base)->assertForbidden();
    }

    public function test_student_report_list_is_filtered_and_teacher_boundary_is_enforced(): void
    {
        [$teacher, $student, $session] = $this->sessionFixture();
        $otherStudent = $this->registerStudent('report_other_student');
        $this->withToken($teacher['token'])->postJson('/api/v1/sessions/'.$session->id.'/end')->assertOk();
        $secondSession = LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $session->halaqa_id, 'teacher_id' => $teacher['user']['id'], 'student_id' => $student['user']['id'], 'task_type_id' => 2, 'state' => 'ended', 'requested_at' => now()->subDay(), 'connected_at' => now()->subDay()->addMinutes(5), 'ended_at' => now()->subDay()->addMinutes(35), 'direct_p2p_only' => true, 'client_operation_id' => (string) Str::uuid()]);
        SessionReport::create(['id' => (string) Str::uuid(), 'session_id' => $secondSession->id, 'state' => 'completed', 'version' => 1]);
        $outsideSession = LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $session->halaqa_id, 'teacher_id' => $teacher['user']['id'], 'student_id' => $otherStudent['user']['id'], 'task_type_id' => 1, 'state' => 'ended', 'requested_at' => now(), 'ended_at' => now(), 'direct_p2p_only' => true, 'client_operation_id' => (string) Str::uuid()]);
        SessionReport::create(['id' => (string) Str::uuid(), 'session_id' => $outsideSession->id, 'state' => 'completed', 'version' => 1]);

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/students/'.$student['user']['id'].'/reports?task_type=review')
            ->assertOk()->assertJsonCount(1, 'reports')->assertJsonPath('reports.0.session_id', $secondSession->id)->assertJsonMissingPath('data');
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->getJson('/api/v1/students/'.$student['user']['id'].'/reports')
            ->assertOk()->assertJsonCount(2, 'reports');
        $this->withToken($teacher['token'])->getJson('/api/v1/students/'.$otherStudent['user']['id'].'/reports')->assertForbidden();
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: LiveSession} */
    private function sessionFixture(): array
    {
        $teacher = $this->registerTeacher('report_teacher');
        $student = $this->registerStudent('report_student');
        $halaqa = Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacher['user']['id'], 'name' => 'Report Halaqa', 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'status' => 'active', 'max_students' => 10, 'timezone' => 'Asia/Riyadh']);
        HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $student['user']['id'], 'status' => 'active', 'joined_at' => now()]);
        $session = LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'teacher_id' => $teacher['user']['id'], 'student_id' => $student['user']['id'], 'task_type_id' => 1, 'state' => 'connected', 'requested_at' => now()->subHour(), 'connected_at' => now()->subMinutes(40), 'direct_p2p_only' => true, 'client_operation_id' => (string) Str::uuid()]);

        return [$teacher, $student, $session];
    }

    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => $prefix, 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 0, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => []], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    private function registerTeacher(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => $prefix, 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 10, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
