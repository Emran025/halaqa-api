<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HalaqaMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_list_update_and_change_halaqa_status(): void
    {
        $teacher = $this->registerTeacher();

        $created = $this->withToken($teacher['token'])->postJson('/api/v1/halaqas', [
            'name' => 'Halaqa Riyadh',
            'description' => 'Evening memorization circle',
            'gender' => 'male',
            'country' => 'Saudi Arabia',
            'residence' => 'Riyadh',
            'max_students' => 2,
            'timezone' => 'Asia/Riyadh',
        ]);

        $created->assertCreated()
            ->assertJsonStructure(['halaqa' => ['id', 'teacher', 'name', 'status', 'student_count', 'max_students', 'available_capacity', 'gender', 'country', 'residence', 'timezone']])
            ->assertJsonPath('halaqa.status', 'active')
            ->assertJsonPath('halaqa.available_capacity', 2)
            ->assertJsonMissingPath('data');

        $halaqaId = $created->json('halaqa.id');
        $this->withToken($teacher['token'])->patchJson('/api/v1/halaqas/'.$halaqaId, ['name' => 'Updated Halaqa'])
            ->assertOk()->assertJsonPath('halaqa.name', 'Updated Halaqa');
        $this->withToken($teacher['token'])->postJson('/api/v1/halaqas/'.$halaqaId.'/deactivate')
            ->assertOk()->assertJsonPath('halaqa.status', 'inactive');
        $this->withToken($teacher['token'])->postJson('/api/v1/halaqas/'.$halaqaId.'/activate')
            ->assertOk()->assertJsonPath('halaqa.status', 'active');
        $this->withToken($teacher['token'])->getJson('/api/v1/halaqas')->assertOk()->assertJsonStructure(['halaqas', 'pagination']);
    }

    public function test_teacher_can_assign_student_and_manage_membership_without_deleting_history(): void
    {
        $teacher = $this->registerTeacher();
        $student = $this->registerStudent();
        $halaqaId = $this->createHalaqa($teacher['token'])['halaqa']['id'];

        $assigned = $this->withToken($teacher['token'])->postJson('/api/v1/halaqas/'.$halaqaId.'/students', ['student_id' => $student['user']['id']]);
        $assigned->assertCreated()->assertJsonStructure(['membership' => ['id', 'halaqa_id', 'student', 'status', 'joined_at']])->assertJsonPath('membership.status', 'active');

        $membershipId = $assigned->json('membership.id');
        $this->withToken($teacher['token'])->getJson('/api/v1/halaqas/'.$halaqaId.'/students')
            ->assertOk()->assertJsonStructure(['students', 'pagination'])->assertJsonCount(1, 'students');
        $this->withToken($teacher['token'])->patchJson('/api/v1/halaqas/'.$halaqaId.'/memberships/'.$membershipId, ['status' => 'inactive', 'reason' => 'Pause requested'])
            ->assertOk()->assertJsonPath('membership.status', 'inactive');
        $this->withToken($teacher['token'])->deleteJson('/api/v1/halaqas/'.$halaqaId.'/memberships/'.$membershipId)->assertNoContent();
        $this->assertDatabaseHas('halaqa_memberships', ['id' => $membershipId, 'status' => 'removed']);
    }

    public function test_capacity_gender_duplicate_and_role_rules_are_enforced(): void
    {
        $teacher = $this->registerTeacher();
        $student = $this->registerStudent();
        $secondStudentPayload = $this->studentPayload('second.student@example.test', '500000003');
        $secondStudent = $this->postJson('/api/v1/auth/register/student', $secondStudentPayload)->assertCreated()->json();
        $halaqaId = $this->createHalaqa($teacher['token'], ['max_students' => 1])['halaqa']['id'];

        $this->withToken($teacher['token'])->postJson('/api/v1/halaqas/'.$halaqaId.'/students', ['student_id' => $student['user']['id']])->assertCreated();
        $this->withToken($teacher['token'])->postJson('/api/v1/halaqas/'.$halaqaId.'/students', ['student_id' => $secondStudent['user']['id']])
            ->assertStatus(409)->assertJsonPath('error.code', 'halaqa_capacity_reached');
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/halaqas', $this->halaqaPayload())->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/halaqas')->assertOk()->assertJsonCount(1, 'halaqas');
    }

    public function test_unknown_halaqa_fields_are_rejected(): void
    {
        $teacher = $this->registerTeacher();
        $payload = $this->halaqaPayload();
        $payload['unexpected'] = true;

        $this->withToken($teacher['token'])->postJson('/api/v1/halaqas', $payload)
            ->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }

    /** @return array<string, mixed> */
    private function registerTeacher(): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', [
            'name' => 'Teacher Example', 'username' => 'teacher_'.Str::lower(Str::random(5)), 'email' => 'teacher_'.Str::lower(Str::random(5)).'@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01',
            'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966',
            'qualification' => 'Ijazah in Quran', 'experience_years' => 12, 'documents' => [], 'client_operation_id' => (string) Str::uuid(),
        ])->assertCreated()->json();
    }

    /** @return array<string, mixed> */
    private function registerStudent(): array
    {
        return $this->postJson('/api/v1/auth/register/student', $this->studentPayload('student.'.Str::lower(Str::random(5)).'@example.test', '500'.random_int(1000000, 9999999)))->assertCreated()->json();
    }

    /** @return array<string, mixed> */
    private function createHalaqa(string $token, array $overrides = []): array
    {
        return $this->withToken($token)->postJson('/api/v1/halaqas', array_merge($this->halaqaPayload(), $overrides))->assertCreated()->json();
    }

    /** @return array<string, mixed> */
    private function halaqaPayload(): array
    {
        return ['name' => 'Halaqa Riyadh', 'description' => null, 'gender' => 'male', 'country' => 'Saudi Arabia', 'residence' => 'Riyadh', 'max_students' => null, 'timezone' => 'Asia/Riyadh'];
    }

    /** @return array<string, mixed> */
    private function studentPayload(string $email, string $phone): array
    {
        return [
            'name' => 'Student Example', 'username' => 'student_'.Str::lower(Str::random(5)), 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
            'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => $phone, 'phone_zone' => '+966',
            'memorization_level' => 'four juz', 'previous_memorization' => ['memorized_juz_count' => 4.5, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => [1]],
            'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30],
            'follow_up_plan' => ['frequency' => 'twiceAWeek', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 2, 'notes' => null]]],
            'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid(),
        ];
    }
}
