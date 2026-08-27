<?php

namespace Tests\Feature;

use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\FollowUpPlanDetail;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FollowUpItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_own_items_and_teacher_sees_only_active_students_in_owned_halaqa(): void
    {
        $teacher = $this->registerTeacher('follow_teacher');
        $student = $this->registerStudent('follow_student');
        $otherStudent = $this->registerStudent('follow_other_student');
        $halaqa = $this->createHalaqa($teacher['user']['id']);
        $this->createMembership($halaqa->id, $student['user']['id']);
        $this->createItem($student['user']['id'], $halaqa->id, 'memorization', '2026-09-02 18:00:00');
        $this->createItem($otherStudent['user']['id'], $halaqa->id, 'review', '2026-09-02 19:00:00');

        $this->withToken($student['token'])->getJson('/api/v1/follow-up-items')
            ->assertOk()->assertJsonCount(1, 'follow_up_items')->assertJsonPath('follow_up_items.0.student_id', $student['user']['id'])->assertJsonMissingPath('data');
        $this->withToken($teacher['token'])->getJson('/api/v1/follow-up-items')
            ->assertOk()->assertJsonCount(1, 'follow_up_items')->assertJsonPath('follow_up_items.0.student_id', $student['user']['id']);
    }

    public function test_teacher_filters_items_by_student_date_state_and_task_type(): void
    {
        $teacher = $this->registerTeacher('filter_teacher');
        $student = $this->registerStudent('filter_student');
        $halaqa = $this->createHalaqa($teacher['user']['id']);
        $this->createMembership($halaqa->id, $student['user']['id']);
        $this->createItem($student['user']['id'], $halaqa->id, 'memorization', '2026-09-02 18:00:00', 'due');
        $this->createItem($student['user']['id'], $halaqa->id, 'review', '2026-09-03 18:00:00', 'upcoming');

        $this->withToken($teacher['token'])->getJson('/api/v1/follow-up-items?date=2026-09-02&state=due&task_type=memorization&student_id='.$student['user']['id'])
            ->assertOk()->assertJsonCount(1, 'follow_up_items')->assertJsonPath('follow_up_items.0.task_type', 'memorization')->assertJsonPath('meta.total', 1);
    }

    public function test_complete_is_idempotent_and_second_different_operation_conflicts(): void
    {
        $student = $this->registerStudent('complete_student');
        $item = $this->createItem($student['user']['id'], null, 'memorization', '2026-09-02 18:00:00');
        $operation = (string) Str::uuid();

        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/complete', ['client_operation_id' => $operation])
            ->assertOk()->assertJsonPath('follow_up_item.state', 'completed');
        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/complete', ['client_operation_id' => $operation])
            ->assertOk()->assertJsonPath('follow_up_item.state', 'completed');
        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/skip', ['reason' => 'Too late', 'client_operation_id' => (string) Str::uuid()])
            ->assertStatus(409)->assertJsonPath('error.code', 'follow_up_item_state_conflict');
        $this->assertDatabaseHas('follow_up_items', ['id' => $item->id, 'state' => 'completed']);
    }

    public function test_skip_requires_strict_shape_and_stores_reason(): void
    {
        $student = $this->registerStudent('skip_student');
        $item = $this->createItem($student['user']['id'], null, 'review', '2026-09-02 18:00:00');

        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/skip', ['reason' => 'مرض', 'client_operation_id' => (string) Str::uuid(), 'unexpected' => true])
            ->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/skip', ['reason' => 'مرض', 'client_operation_id' => (string) Str::uuid()])
            ->assertOk()->assertJsonPath('follow_up_item.state', 'skipped')->assertJsonPath('follow_up_item.skip_reason', 'مرض');
    }

    public function test_reschedule_creates_linked_item_and_retry_returns_same_item(): void
    {
        $student = $this->registerStudent('reschedule_student');
        $item = $this->createItem($student['user']['id'], null, 'recitation', '2026-09-02 18:00:00');
        $operation = (string) Str::uuid();
        $payload = ['scheduled_at' => '2026-09-04T20:30:00+03:00', 'timezone' => 'Asia/Riyadh', 'reason' => 'موعد جديد', 'client_operation_id' => $operation];

        $first = $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/reschedule', $payload)
            ->assertOk()->assertJsonPath('follow_up_item.state', 'upcoming')->assertJsonPath('follow_up_item.rescheduled_from_id', $item->id);
        $newId = $first->json('follow_up_item.id');
        $this->withToken($student['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/reschedule', $payload)
            ->assertOk()->assertJsonPath('follow_up_item.id', $newId);
        $this->assertDatabaseHas('follow_up_items', ['id' => $item->id, 'state' => 'skipped', 'last_client_operation_id' => $operation]);
        $this->assertDatabaseHas('follow_up_items', ['id' => $newId, 'rescheduled_from_id' => $item->id, 'timezone' => 'Asia/Riyadh']);
    }

    public function test_unrelated_teacher_cannot_view_or_mutate_student_item(): void
    {
        $ownerTeacher = $this->registerTeacher('owner_teacher');
        $otherTeacher = $this->registerTeacher('unrelated_teacher');
        $student = $this->registerStudent('boundary_student');
        $halaqa = $this->createHalaqa($ownerTeacher['user']['id']);
        $this->createMembership($halaqa->id, $student['user']['id']);
        $item = $this->createItem($student['user']['id'], $halaqa->id, 'memorization', '2026-09-02 18:00:00');

        $this->withToken($otherTeacher['token'])->getJson('/api/v1/follow-up-items')->assertOk()->assertJsonCount(0, 'follow_up_items');
        $this->withToken($otherTeacher['token'])->postJson('/api/v1/follow-up-items/'.$item->id.'/complete', ['client_operation_id' => (string) Str::uuid()])->assertForbidden();
    }

    private function createItem(string $studentId, ?string $halaqaId, string $taskType, string $scheduledFor, string $state = 'upcoming'): FollowUpItem
    {
        $plan = FollowUpPlan::create(['id' => (string) Str::uuid(), 'student_id' => $studentId, 'created_by_user_id' => $studentId, 'frequency' => 'daily', 'status' => 'active', 'timezone' => 'Asia/Riyadh', 'version' => 1]);
        $detail = FollowUpPlanDetail::create(['id' => (string) Str::uuid(), 'plan_id' => $plan->id, 'tracking_type_id' => ['memorization' => 1, 'review' => 2, 'recitation' => 3][$taskType], 'tracking_unit_id' => 5, 'amount' => 1, 'notes' => null, 'sort_order' => 1]);

        return FollowUpItem::create(['id' => (string) Str::uuid(), 'plan_id' => $plan->id, 'plan_detail_id' => $detail->id, 'student_id' => $studentId, 'halaqa_id' => $halaqaId, 'scheduled_for' => $scheduledFor, 'timezone' => 'Asia/Riyadh', 'state' => $state]);
    }

    private function createHalaqa(string $teacherId): Halaqa
    {
        return Halaqa::create(['id' => (string) Str::uuid(), 'teacher_id' => $teacherId, 'name' => 'Test Halaqa', 'description' => null, 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'status' => 'active', 'max_students' => 20, 'timezone' => 'Asia/Riyadh']);
    }

    private function createMembership(string $halaqaId, string $studentId): HalaqaMembership
    {
        return HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqaId, 'student_id' => $studentId, 'status' => 'active', 'joined_at' => now()]);
    }

    private function registerStudent(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/student', ['name' => $prefix, 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 1, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => [1]], 'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30], 'follow_up_plan' => ['frequency' => 'daily', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null]]], 'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }

    private function registerTeacher(string $prefix): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', ['name' => $prefix, 'username' => $prefix.'_'.Str::lower(Str::random(6)), 'email' => $prefix.'_'.Str::lower(Str::random(6)).'@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah', 'experience_years' => 5, 'documents' => [], 'client_operation_id' => (string) Str::uuid()])->assertCreated()->json();
    }
}
