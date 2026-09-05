<?php

namespace App\Services\Auth;

use App\Exceptions\ApiConflictException;
use App\Models\FollowUpPlan;
use App\Models\FollowUpPlanDetail;
use App\Models\Notification;
use App\Models\RegistrationRequest;
use App\Models\RegistrationRequestAvailability;
use App\Models\RegistrationRequestAvailabilitySlot;
use App\Models\RegistrationRequestProfile;
use App\Models\StudentAvailabilityProfile;
use App\Models\StudentAvailabilitySlot;
use App\Models\StudentProfile;
use App\Models\TeacherDocument;
use App\Models\TeacherProfile;
use App\Models\TrackingType;
use App\Models\TrackingUnit;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(private readonly EmailVerificationService $emailVerification) {}

    /** @return array{user: User, token: string, expires_at: string} */
    public function registerStudent(array $data): array
    {
        $existing = $this->existingByOperation($data, 'student');
        if ($existing !== null) {
            $this->sendVerificationIfNeeded($existing);

            return $this->issueToken($existing);
        }

        $user = DB::transaction(function () use ($data): User {
            $user = $this->createUser($data, 'student');
            $previous = $data['previous_memorization'] ?? [];

            StudentProfile::create([
                'user_id' => $user->id,
                'memorization_level' => $data['memorization_level'] ?? ($previous['memorization_level'] ?? null),
                'review_level' => $data['review_level'] ?? ($previous['review_level'] ?? null),
                'memorized_juz_count' => $previous['memorized_juz_count'] ?? null,
                'memorized_surah_ids' => $previous['memorized_surah_ids'] ?? null,
                'last_completed_unit' => $previous['last_completed_unit'] ?? null,
                'previous_memorization_notes' => $previous['previous_teacher_notes'] ?? null,
                'stop_reasons' => $previous['stop_reasons'] ?? null,
                'bio' => $data['profile_bio'] ?? null,
            ]);

            $this->createAvailability($user, $data['attendance_preferences']);
            $registrationRequest = $this->createRegistrationRequest($user, $data, $previous);
            $this->createFollowUpPlan($user, $data['follow_up_plan'], $data['attendance_preferences']['timezone'], $registrationRequest->id);

            return $user->fresh(['studentProfile', 'studentProfile.availability', 'studentProfile.followUpPlan.details']);
        });

        $this->sendVerificationIfNeeded($user);

        return $this->issueToken($user);
    }

    /** @return array{user: User, token: string, expires_at: string} */
    public function registerTeacher(array $data): array
    {
        $existing = $this->existingByOperation($data, 'teacher');
        if ($existing !== null) {
            $this->sendVerificationIfNeeded($existing);

            return $this->issueToken($existing);
        }

        $user = DB::transaction(function () use ($data): User {
            $user = $this->createUser($data, 'teacher');
            $profile = TeacherProfile::create([
                'user_id' => $user->id,
                'teacher_code' => $this->generateTeacherCode(),
                'qualification' => $data['qualification'],
                'experience_years' => $data['experience_years'],
                'bio' => $data['bio'] ?? null,
                'available_time' => $data['available_time'] ?? null,
                'max_halaqas' => $data['max_halaqas'] ?? 0,
            ]);

            foreach ($data['documents'] ?? [] as $document) {
                TeacherDocument::create([
                    'teacher_id' => $user->id,
                    'name' => $document['name'],
                    'certificate_type' => $document['certificate_type'],
                    'certificate_type_other' => $document['certificate_type_other'] ?? null,
                    'riwayah' => $document['riwayah'] ?? null,
                    'issuing_place' => $document['issuing_place'] ?? null,
                    'issuing_date' => $document['issuing_date'] ?? null,
                    'storage_path' => $document['file_url'] ?? null,
                ]);
            }

            return $user->fresh(['teacherProfile', 'teacherProfile.documents']);
        });

        $this->sendVerificationIfNeeded($user);

        return $this->issueToken($user);
    }

    /** @return array{user: User, token: string, expires_at: string} */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->isActive() || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials are invalid.');
        }

        if ($user->email_verified_at === null) {
            throw new AuthenticationException('حسابك غير مفعّل. تحقق من بريدك الإلكتروني أو اطلب إعادة إرسال رسالة التفعيل.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->issueToken($user->fresh());
    }

    public function logout(User $user, ?string $plainTextToken): void
    {
        // The current contract exposes one account session; revoke every active
        // token so a replayed desktop token cannot continue after logout.
        if ($plainTextToken !== null) {
            $user->tokens()->delete();
        }
    }

    /** @return array{user: User, token: string, expires_at: string} */
    private function issueToken(User $user): array
    {
        $expiresAt = now()->addDays(30);
        $token = $user->createToken('wpf-client', ['*'], $expiresAt);

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    private function createUser(array $data, string $role): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'client_operation_id' => $data['client_operation_id'],
            'role' => $role,
            'username' => $data['username'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'gender' => $data['gender'],
            'birth_date' => $data['birth_date'],
            'country' => $data['country'],
            'city' => $data['city'],
            'residence' => $data['residence'] ?? null,
            'phone' => $data['phone'],
            'phone_zone' => $data['phone_zone'],
            'whatsapp_phone' => $data['whatsapp_phone'] ?? null,
            'whatsapp_zone' => $data['whatsapp_zone'] ?? null,
            'status' => 'active',
        ]);
    }

    private function sendVerificationIfNeeded(User $user): void
    {
        try {
            $this->emailVerification->send($user);
        } catch (\Throwable $exception) {
            Log::error('Unable to send account verification email.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function existingByOperation(array $data, string $role): ?User
    {
        $operationId = $data['client_operation_id'] ?? null;
        if ($operationId === null) {
            return null;
        }

        $existing = User::query()->where('client_operation_id', $operationId)->first();
        if ($existing !== null && $existing->role !== $role) {
            throw new ApiConflictException('The client operation id was already used for another account role.', 'client_operation_reused', 'user', $existing->id);
        }

        return $existing?->fresh($role === 'student'
            ? ['studentProfile', 'studentProfile.availability', 'studentProfile.followUpPlan.details']
            : ['teacherProfile', 'teacherProfile.documents']);
    }

    private function generateTeacherCode(): string
    {
        do {
            $code = 'TCH-'.strtoupper(Str::random(8));
        } while (TeacherProfile::query()->where('teacher_code', $code)->exists());

        return $code;
    }

    private function createAvailability(User $student, array $preferences): void
    {
        StudentAvailabilityProfile::create([
            'student_id' => $student->id,
            'timezone' => $preferences['timezone'],
            'preferred_session_duration_minutes' => $preferences['preferred_session_duration_minutes'] ?? 30,
        ]);

        foreach ($preferences['weekly_slots'] as $slot) {
            StudentAvailabilitySlot::create([
                'student_id' => $student->id,
                'day_of_week' => $slot['day_of_week'],
                'available_from' => $slot['from'],
                'available_to' => $slot['to'],
                'is_preferred' => $slot['preferred'] ?? false,
            ]);
        }
    }

    private function createFollowUpPlan(User $student, array $input, string $timezone, string $sourceRegistrationRequestId): void
    {
        $plan = FollowUpPlan::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'created_by_user_id' => $student->id,
            'frequency' => $input['frequency'],
            'status' => 'draft',
            'source_registration_request_id' => $sourceRegistrationRequestId,
            'timezone' => $timezone,
            'starts_on' => $input['starts_on'] ?? null,
            'ends_on' => $input['ends_on'] ?? null,
            'version' => 1,
        ]);

        $typeIds = TrackingType::query()->pluck('id', 'code');
        $unitIds = TrackingUnit::query()->pluck('id', 'code');

        foreach ($input['details'] as $index => $detail) {
            FollowUpPlanDetail::create([
                'id' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'tracking_type_id' => $typeIds[$detail['task_type']],
                'tracking_unit_id' => $unitIds[$detail['unit']],
                'amount' => $detail['amount'],
                'notes' => $detail['notes'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function createRegistrationRequest(User $student, array $data, array $previous): RegistrationRequest
    {
        $teacher = null;
        if (! empty($data['teacher_code'])) {
            $teacher = TeacherProfile::query()
                ->where('teacher_code', $data['teacher_code'])
                ->firstOrFail()
                ->user;
        }

        $request = RegistrationRequest::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'teacher_id' => $teacher?->id,
            'teacher_code_snapshot' => $data['teacher_code'] ?? null,
            'routing_mode' => $teacher ? 'specific_teacher' : 'all_available_teachers',
            'state' => 'pending',
            'submitted_at' => now(),
        ]);

        RegistrationRequestProfile::create([
            'registration_request_id' => $request->id,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date,
            'country' => $student->country,
            'city' => $student->city,
            'residence' => $student->residence,
            'phone' => $student->phone,
            'phone_zone' => $student->phone_zone,
            'whatsapp_phone' => $student->whatsapp_phone,
            'whatsapp_zone' => $student->whatsapp_zone,
            'memorization_level' => $student->studentProfile->memorization_level,
            'review_level' => $student->studentProfile->review_level,
            'memorized_juz_count' => $previous['memorized_juz_count'] ?? null,
            'memorized_surah_ids' => $previous['memorized_surah_ids'] ?? null,
            'last_completed_unit' => $previous['last_completed_unit'] ?? null,
            'previous_memorization_notes' => $previous['previous_teacher_notes'] ?? null,
            'stop_reasons' => $previous['stop_reasons'] ?? null,
            'profile_bio' => $data['profile_bio'] ?? null,
        ]);

        $attendance = $data['attendance_preferences'];
        RegistrationRequestAvailability::create([
            'registration_request_id' => $request->id,
            'timezone' => $attendance['timezone'],
            'preferred_session_duration_minutes' => $attendance['preferred_session_duration_minutes'] ?? 30,
        ]);
        foreach ($attendance['weekly_slots'] as $slot) {
            RegistrationRequestAvailabilitySlot::create([
                'registration_request_id' => $request->id,
                'day_of_week' => $slot['day_of_week'],
                'available_from' => $slot['from'],
                'available_to' => $slot['to'],
                'is_preferred' => $slot['preferred'] ?? false,
            ]);
        }

        $teacherIds = $teacher !== null
            ? collect([$teacher->id])
            : User::query()->where('role', 'teacher')->where('status', 'active')
                ->where('gender', $student->gender)
                ->where('country', $student->country)
                ->pluck('id');
        foreach ($teacherIds as $teacherId) {
            Notification::firstOrCreate(
                ['dedupe_key' => 'registration-request:submitted:'.$request->id.':'.$teacherId],
                [
                    'id' => (string) Str::uuid(),
                    'user_id' => $teacherId,
                    'type' => 'registration_request',
                    'title' => 'Registration request update',
                    'body' => 'A new student registration request is available.',
                    'payload' => ['entity_type' => 'registration_request', 'entity_id' => (string) $request->id, 'action' => 'view'],
                ],
            );
        }

        return $request;
    }
}
