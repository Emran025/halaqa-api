<?php

namespace Tests\Feature;

use App\Models\Halaqa;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DirectHalaqaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_direct_halaqa_request_and_owner_can_list_it(): void
    {
        $teacher = User::factory()->teacher()->create(['gender' => 'male', 'country' => 'Saudi Arabia']);
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-DIRECT-'.$teacher->id, 'qualification' => 'Ijazah']);
        $halaqa = Halaqa::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $teacher->id,
            'name' => 'Direct Halaqa',
            'gender' => 'male',
            'country' => 'Saudi Arabia',
            'residence' => 'Riyadh',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $student = User::factory()->student()->create(['gender' => 'male', 'country' => 'Saudi Arabia']);
        $studentToken = $student->createToken('test')->plainTextToken;

        $response = $this->withToken($studentToken)->postJson('/api/v1/halaqas/'.$halaqa->id.'/registration-requests', $this->registrationPayload());
        $response->assertCreated()
            ->assertJsonPath('registration_request.requested_halaqa.id', (string) $halaqa->id)
            ->assertJsonMissingPath('data');

        app('auth')->forgetGuards();
        $teacherToken = $teacher->createToken('test')->plainTextToken;
        $this->withToken($teacherToken)->getJson('/api/v1/halaqas/'.$halaqa->id.'/registration-requests?state=pending')
            ->assertOk()
            ->assertJsonStructure(['registration_requests', 'meta'])
            ->assertJsonPath('registration_requests.0.requested_halaqa.id', (string) $halaqa->id);
    }

    public function test_only_halaqa_owner_can_read_direct_registration_inbox(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $halaqa = Halaqa::create([
            'id' => (string) Str::uuid(),
            'teacher_id' => $owner->id,
            'name' => 'Owned Halaqa',
            'gender' => $owner->gender,
            'country' => $owner->country,
            'residence' => 'Riyadh',
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $ownerToken = $owner->createToken('test')->plainTextToken;
        $otherToken = $otherTeacher->createToken('test')->plainTextToken;
        $student = User::factory()->student()->create();
        $studentToken = $student->createToken('test')->plainTextToken;

        $this->withToken($otherToken)->getJson('/api/v1/halaqas/'.$halaqa->id.'/registration-requests')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($studentToken)->getJson('/api/v1/halaqas/'.$halaqa->id.'/registration-requests')->assertForbidden();
        app('auth')->forgetGuards();
        $this->withToken($ownerToken)->getJson('/api/v1/halaqas/'.$halaqa->id.'/registration-requests')->assertOk();
    }

    /** @return array<string, mixed> */
    private function registrationPayload(): array
    {
        return [
            'message' => 'I would like to join this halaqa.',
            'client_operation_id' => (string) Str::uuid(),
            'profile' => [
                'gender' => 'male',
                'birth_date' => '2000-01-01',
                'country' => 'Saudi Arabia',
                'city' => 'Riyadh',
                'residence' => 'Riyadh',
                'phone' => '500000010',
                'phone_zone' => '+966',
                'bio' => 'Student profile',
            ],
            'previous_memorization' => [
                'memorized_juz_count' => 3,
                'memorized_surah_ids' => [1, 2],
                'last_completed_unit' => ['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null],
                'previous_teacher_notes' => 'Ready',
                'stop_reasons' => null,
            ],
            'attendance_preferences' => [
                'timezone' => 'Asia/Riyadh',
                'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']],
            ],
            'follow_up_plan' => [
                'frequency' => 'onceAWeek',
                'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]],
            ],
        ];
    }
}
