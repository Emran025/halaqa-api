<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use App\Models\RegistrationRequestProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentApplicationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_matching_general_applications_as_public_summaries(): void
    {
        $teacher = User::factory()->teacher()->create(['gender' => 'male', 'country' => 'Saudi Arabia']);
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-INBOX-'.$teacher->id, 'qualification' => 'Ijazah']);
        $student = User::factory()->student()->create(['name' => 'Applicant Student', 'gender' => 'male', 'country' => 'Saudi Arabia']);
        $request = RegistrationRequest::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'routing_mode' => 'all_available_teachers',
            'state' => 'pending',
            'submitted_at' => now(),
        ]);
        RegistrationRequestProfile::create([
            'registration_request_id' => $request->id,
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'phone' => '500000001',
            'phone_zone' => '+966',
            'memorized_juz_count' => 5,
        ]);
        $token = $teacher->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/student-applications?search=Applicant')
            ->assertOk()
            ->assertJsonStructure(['applicants', 'meta'])
            ->assertJsonPath('applicants.0.student_summary.display_name', 'Applicant Student')
            ->assertJsonPath('applicants.0.student_summary.sensitive_fields_hidden', true)
            ->assertJsonPath('applicants.0.profile', null)
            ->assertJsonPath('applicants.0.previous_memorization', null)
            ->assertJsonPath('applicants.0.attendance_preferences', null)
            ->assertJsonPath('applicants.0.follow_up_plan', null)
            ->assertJsonMissingPath('applicants.0.profile.phone')
            ->assertJsonMissingPath('data');
    }

    public function test_inbox_uses_registration_snapshot_for_completion_requested(): void
    {
        $teacher = User::factory()->teacher()->create(['gender' => 'male', 'country' => 'Saudi Arabia']);
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-COMPLETION-'.$teacher->id, 'qualification' => 'Ijazah']);
        $student = User::factory()->student()->create(['name' => 'Snapshot Applicant', 'gender' => 'female', 'country' => 'Egypt']);
        $request = RegistrationRequest::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'routing_mode' => 'all_available_teachers',
            'state' => 'completion_requested',
            'submitted_at' => now(),
        ]);
        RegistrationRequestProfile::create([
            'registration_request_id' => $request->id,
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'phone' => '500000002',
            'phone_zone' => '+966',
        ]);
        $token = $teacher->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/student-applications?state=completion_requested')
            ->assertOk()
            ->assertJsonPath('applicants.0.student_summary.display_name', 'Snapshot Applicant')
            ->assertJsonPath('applicants.0.profile', null);
    }

    public function test_student_cannot_access_teacher_application_inbox_and_query_is_strict(): void
    {
        $student = User::factory()->student()->create();
        $token = $student->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/student-applications')->assertForbidden();
        $teacher = User::factory()->teacher()->create();
        TeacherProfile::create(['user_id' => $teacher->id, 'teacher_code' => 'TCH-STRICT-'.$teacher->id, 'qualification' => 'Ijazah']);
        $teacherToken = $teacher->createToken('test')->plainTextToken;
        app('auth')->forgetGuards();
        $this->withToken($teacherToken)->getJson('/api/v1/student-applications?unexpected=true')
            ->assertUnprocessable()
            ->assertJsonPath('field_errors.0.field', '_schema');
    }
}
