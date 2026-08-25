<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_accept_general_request_and_privacy_opens_only_after_acceptance(): void
    {
        $teacher = $this->registerTeacher();
        $student = $this->registerStudent();
        $registrationId = $this->registrationIdFor($student['user']['id']);

        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->getJson('/api/v1/registration-requests')
            ->assertOk()->assertJsonStructure(['registration_requests', 'pagination'])->assertJsonPath('registration_requests.0.profile', null);
        $this->withToken($teacher['token'])->getJson('/api/v1/registration-requests/'.$registrationId)
            ->assertOk()->assertJsonPath('registration_request.visibility', 'public_summary')
            ->assertJsonPath('registration_request.profile', null);

        app('auth')->forgetGuards();
        $accepted = $this->withToken($teacher['token'])->postJson('/api/v1/registration-requests/'.$registrationId.'/accept');
        $accepted->assertOk()->assertJsonPath('registration_request.state', 'accepted')->assertJsonPath('registration_request.visibility', 'relationship_visible');
        $this->assertDatabaseHas('registration_requests', ['id' => $registrationId, 'state' => 'accepted', 'teacher_id' => $teacher['user']['id']]);

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->getJson('/api/v1/registration-requests/'.$registrationId)
            ->assertOk()->assertJsonPath('registration_request.visibility', 'student_visible')->assertJsonPath('registration_request.profile.gender', 'male');
    }

    public function test_student_can_cancel_open_request_and_teacher_can_reject_with_note(): void
    {
        $teacher = $this->registerTeacher();
        $student = $this->registerStudent();
        $registrationId = $this->registrationIdFor($student['user']['id']);

        $this->withToken($student['token'])->deleteJson('/api/v1/registration-requests/'.$registrationId)->assertNoContent();
        $this->assertDatabaseHas('registration_requests', ['id' => $registrationId, 'state' => 'cancelled']);

        app('auth')->forgetGuards();
        $secondStudent = $this->registerStudent('second.registration@example.test', '500000009');
        $secondRegistrationId = $this->registrationIdFor($secondStudent['user']['id']);
        app('auth')->forgetGuards();
        $this->withToken($teacher['token'])->postJson('/api/v1/registration-requests/'.$secondRegistrationId.'/reject', ['note' => 'Please choose a suitable schedule.'])
            ->assertOk()->assertJsonPath('registration_request.state', 'rejected');
        $this->assertDatabaseHas('registration_requests', ['id' => $secondRegistrationId, 'state' => 'rejected', 'decision_note' => 'Please choose a suitable schedule.']);
    }

    public function test_open_duplicate_registration_is_rejected_and_unknown_fields_are_rejected(): void
    {
        $student = $this->registerStudent();
        $payload = $this->registrationPayload();
        $this->withToken($student['token'])->postJson('/api/v1/registration-requests', $payload)->assertStatus(409)->assertJsonPath('error.code', 'open_registration_exists');

        app('auth')->forgetGuards();
        $this->withToken($student['token'])->postJson('/api/v1/registration-requests', array_merge($payload, ['unexpected' => true]))
            ->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }

    /** @return array<string, mixed> */
    private function registerTeacher(): array
    {
        return $this->postJson('/api/v1/auth/register/teacher', [
            'name' => 'Teacher Example', 'username' => 'teacher_'.Str::lower(Str::random(6)), 'email' => 'teacher_'.Str::lower(Str::random(6)).'@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'gender' => 'male', 'birth_date' => '1980-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh',
            'phone' => '500'.random_int(1000000, 9999999), 'phone_zone' => '+966', 'qualification' => 'Ijazah in Quran', 'experience_years' => 12, 'documents' => [], 'client_operation_id' => (string) Str::uuid(),
        ])->assertCreated()->json();
    }

    /** @return array<string, mixed> */
    private function registerStudent(string $email = 'student.registration@example.test', string $phone = '500000008'): array
    {
        return $this->postJson('/api/v1/auth/register/student', [
            'name' => 'Student Registration', 'username' => 'student_'.Str::lower(Str::random(6)), 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
            'gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => $phone, 'phone_zone' => '+966',
            'memorization_level' => 'beginner', 'previous_memorization' => ['memorized_juz_count' => 1, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => [1]],
            'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30],
            'follow_up_plan' => ['frequency' => 'twiceAWeek', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null]]],
            'teacher_code' => null, 'profile_bio' => null, 'client_operation_id' => (string) Str::uuid(),
        ])->assertCreated()->json();
    }

    private function registrationIdFor(string $studentId): string
    {
        return (string) RegistrationRequest::query()->where('student_id', $studentId)->latest('created_at')->value('id');
    }

    /** @return array<string, mixed> */
    private function registrationPayload(): array
    {
        return [
            'teacher_code' => null, 'requested_halaqa_id' => null, 'message' => 'I would like to join.', 'client_operation_id' => (string) Str::uuid(),
            'profile' => ['gender' => 'male', 'birth_date' => '2000-01-01', 'country' => 'Saudi Arabia', 'city' => 'Riyadh', 'residence' => 'Riyadh', 'phone' => '500000011', 'phone_zone' => '+966', 'whatsapp_phone' => null, 'whatsapp_zone' => null, 'memorization_level' => 'beginner', 'review_level' => null, 'bio' => null],
            'previous_memorization' => ['memorized_juz_count' => 1, 'previous_teacher_notes' => null, 'stop_reasons' => null, 'memorized_surah_ids' => [1]],
            'attendance_preferences' => ['timezone' => 'Asia/Riyadh', 'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]], 'preferred_session_duration_minutes' => 30],
            'follow_up_plan' => ['frequency' => 'twiceAWeek', 'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1, 'notes' => null]]],
        ];
    }
}
