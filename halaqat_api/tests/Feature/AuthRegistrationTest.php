<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_new_account_requires_email_verification_and_can_request_resend(): void
    {
        $response = $this->postJson('/api/v1/auth/register/student', $this->studentPayload())->assertCreated();
        $userId = $response->json('user.id');

        $this->assertDatabaseHas('users', ['id' => $userId, 'email_verified_at' => null]);
        Notification::assertSentTo(User::find($userId), VerifyEmailNotification::class);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'student@example.test',
            'password' => 'password123',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/auth/email/resend-verification', ['email' => 'student@example.test'])
            ->assertAccepted();
    }

    public function test_student_registration_creates_complete_account_domain_and_explicit_response(): void
    {
        $payload = $this->studentPayload();

        $response = $this->postJson('/api/v1/auth/register/student', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'role', 'name', 'email', 'phone', 'status'], 'token', 'token_type', 'expires_at'])
            ->assertJsonPath('user.role', 'student')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonMissingPath('data');

        $userId = $response->json('user.id');
        $this->assertDatabaseHas('users', ['id' => $userId, 'role' => 'student', 'email' => $payload['email']]);
        $this->assertDatabaseHas('student_profiles', ['user_id' => $userId, 'memorized_juz_count' => 4.5]);
        $studentProfile = DB::table('student_profiles')->where('user_id', $userId)->first();
        $this->assertSame([1, 2], json_decode($studentProfile->memorized_surah_ids, true));
        $this->assertSame('page', json_decode($studentProfile->last_completed_unit, true)['unit']);
        $registrationProfile = DB::table('registration_request_profiles')->first();
        $this->assertSame([1, 2], json_decode($registrationProfile->memorized_surah_ids, true));
        $this->assertSame('page', json_decode($registrationProfile->last_completed_unit, true)['unit']);
        $this->assertDatabaseHas('registration_requests', ['student_id' => $userId, 'routing_mode' => 'all_available_teachers', 'state' => 'pending']);
        $this->assertDatabaseCount('registration_request_profiles', 1);
        $this->assertDatabaseHas('registration_request_availability', ['timezone' => 'Asia/Riyadh', 'preferred_session_duration_minutes' => 30]);
        $this->assertDatabaseCount('registration_request_availability_slots', 1);
        $this->assertDatabaseHas('student_availability_profiles', ['student_id' => $userId, 'timezone' => 'Asia/Riyadh']);
        $this->assertDatabaseCount('student_availability_slots', 1);
        $this->assertDatabaseHas('follow_up_plans', ['student_id' => $userId, 'frequency' => 'twiceAWeek', 'timezone' => 'Asia/Riyadh']);
        $this->assertDatabaseCount('follow_up_plans', 1);
        $this->assertDatabaseHas('follow_up_plans', ['source_registration_request_id' => DB::table('registration_requests')->value('id')]);
        $this->assertDatabaseCount('follow_up_plan_details', 2);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_student_registration_accepts_only_the_required_student_ui_fields(): void
    {
        $payload = [
            'name' => 'UI Student',
            'email' => 'ui.student@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'phone' => '500000003',
            'phone_zone' => '+966',
            'attendance_preferences' => [
                'timezone' => 'Asia/Riyadh',
                'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00']],
            ],
            'follow_up_plan' => [
                'frequency' => 'daily',
                'details' => [['task_type' => 'memorization', 'unit' => 'page', 'amount' => 1]],
            ],
            'client_operation_id' => (string) Str::uuid(),
        ];

        $response = $this->postJson('/api/v1/auth/register/student', $payload);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'student')
            ->assertJsonPath('user.email', $payload['email']);

        $userId = $response->json('user.id');
        $this->assertDatabaseHas('users', ['id' => $userId, 'role' => 'student']);
        $this->assertDatabaseHas('student_availability_profiles', ['student_id' => $userId, 'timezone' => 'Asia/Riyadh']);
        $this->assertDatabaseHas('follow_up_plans', ['student_id' => $userId, 'frequency' => 'daily']);
    }

    public function test_repeating_student_client_operation_is_idempotent_for_account_creation(): void
    {
        $payload = $this->studentPayload();
        $first = $this->postJson('/api/v1/auth/register/student', $payload)->assertCreated();
        app('auth')->forgetGuards();
        $second = $this->postJson('/api/v1/auth/register/student', $payload)->assertCreated();

        $this->assertSame($first->json('user.id'), $second->json('user.id'));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('registration_requests', 1);
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_teacher_registration_creates_profile_documents_and_explicit_response(): void
    {
        $payload = $this->teacherPayload();

        $response = $this->postJson('/api/v1/auth/register/teacher', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'role', 'name', 'email', 'phone', 'status'], 'token', 'token_type', 'expires_at'])
            ->assertJsonPath('user.role', 'teacher')
            ->assertJsonMissingPath('data');

        $userId = $response->json('user.id');
        $this->assertDatabaseHas('teacher_profiles', ['user_id' => $userId, 'qualification' => 'Ijazah in Quran']);
        $this->assertDatabaseHas('teacher_documents', ['teacher_id' => $userId, 'certificate_type' => 'ijazah']);
    }

    public function test_unknown_root_or_nested_fields_are_rejected(): void
    {
        $payload = $this->studentPayload();
        $payload['unexpected'] = true;
        $payload['attendance_preferences']['weekly_slots'][0]['unknown'] = true;

        $this->postJson('/api/v1/auth/register/student', $payload)
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'field_errors' => [['field', 'messages']]])
            ->assertJsonPath('field_errors.0.field', '_schema');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_me_and_logout_use_sanctum_token(): void
    {
        $registration = $this->postJson('/api/v1/auth/register/teacher', $this->teacherPayload())->assertCreated();
        DB::table('users')->where('email', 'teacher@example.test')->update(['email_verified_at' => now()]);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'teacher@example.test',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['user', 'token', 'token_type', 'expires_at'])
            ->assertJsonMissingPath('data');

        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure(['user'])
            ->assertJsonPath('user.email', 'teacher@example.test')
            ->assertJsonMissingPath('data');

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertNotEmpty($registration->json('token'));
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->postJson('/api/v1/auth/register/teacher', $this->teacherPayload())->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'teacher@example.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    /** @return array<string, mixed> */
    private function studentPayload(): array
    {
        return [
            'name' => 'Student Example',
            'username' => 'student_example',
            'email' => 'student@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'residence' => 'Riyadh',
            'phone' => '500000001',
            'phone_zone' => '+966',
            'memorization_level' => 'four juz',
            'previous_memorization' => [
                'memorized_juz_count' => 4.5,
                'previous_teacher_notes' => 'Good foundation',
                'stop_reasons' => 'Schedule conflict',
                'memorized_surah_ids' => [1, 2],
                'last_completed_unit' => ['task_type' => 'memorization', 'unit' => 'page', 'amount' => 2, 'notes' => 'Completed'],
            ],
            'attendance_preferences' => [
                'timezone' => 'Asia/Riyadh',
                'weekly_slots' => [['day_of_week' => 0, 'from' => '18:00', 'to' => '19:00', 'preferred' => true]],
                'preferred_session_duration_minutes' => 30,
            ],
            'follow_up_plan' => [
                'frequency' => 'twiceAWeek',
                'details' => [
                    ['task_type' => 'memorization', 'unit' => 'page', 'amount' => 2, 'notes' => null],
                    ['task_type' => 'review', 'unit' => 'juz', 'amount' => 1, 'notes' => 'Weekly review'],
                ],
            ],
            'teacher_code' => null,
            'profile_bio' => null,
            'client_operation_id' => (string) Str::uuid(),
        ];
    }

    /** @return array<string, mixed> */
    private function teacherPayload(): array
    {
        return [
            'name' => 'Teacher Example',
            'username' => 'teacher_example',
            'email' => 'teacher@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'male',
            'birth_date' => '1980-01-01',
            'country' => 'Saudi Arabia',
            'city' => 'Riyadh',
            'phone' => '500000002',
            'phone_zone' => '+966',
            'qualification' => 'Ijazah in Quran',
            'experience_years' => 12,
            'documents' => [['name' => 'Ijazah', 'certificate_type' => 'ijazah', 'file_url' => null]],
            'client_operation_id' => (string) Str::uuid(),
        ];
    }
}
