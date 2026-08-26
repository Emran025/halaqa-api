<?php

namespace App\Services\Registrations;

use App\Exceptions\ApiConflictException;
use App\Models\FollowUpPlan;
use App\Models\FollowUpPlanDetail;
use App\Models\Halaqa;
use App\Models\HalaqaMembership;
use App\Models\Notification;
use App\Models\RegistrationRequest;
use App\Models\RegistrationRequestAvailability;
use App\Models\RegistrationRequestAvailabilitySlot;
use App\Models\RegistrationRequestProfile;
use App\Models\TeacherProfile;
use App\Models\TrackingType;
use App\Models\TrackingUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationService
{
    public function submit(User $student, array $data): RegistrationRequest
    {
        return DB::transaction(function () use ($student, $data): RegistrationRequest {
            $existing = RegistrationRequest::query()->where('student_id', $student->id)->where('client_operation_id', $data['client_operation_id'])->first();
            if ($existing !== null) {
                return $existing->load(['student.studentProfile.availability', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots']);
            }

            $open = RegistrationRequest::query()->where('student_id', $student->id)->whereIn('state', ['pending', 'completion_requested'])->exists();
            if ($open) {
                throw new ApiConflictException('The student already has an open registration request.', 'open_registration_exists', 'user', $student->id);
            }

            $teacher = ! empty($data['teacher_code'])
                ? TeacherProfile::query()->where('teacher_code', $data['teacher_code'])->firstOrFail()->user
                : null;
            $halaqa = ! empty($data['requested_halaqa_id']) ? Halaqa::query()->lockForUpdate()->findOrFail($data['requested_halaqa_id']) : null;
            if ($halaqa && $teacher && $halaqa->teacher_id !== $teacher->id) {
                throw new ApiConflictException('The requested halaqa does not belong to the selected teacher.', 'routing_target_mismatch', 'halaqa', $halaqa->id);
            }
            $profile = $data['profile'];
            if ($halaqa && ($halaqa->gender !== $profile['gender'] || $halaqa->country !== $profile['country'] || $halaqa->status !== 'active')) {
                throw new ApiConflictException('The requested halaqa is not available for this student.', 'halaqa_not_available', 'halaqa', $halaqa->id);
            }

            $request = RegistrationRequest::create([
                'id' => (string) Str::uuid(), 'student_id' => $student->id, 'client_operation_id' => $data['client_operation_id'], 'teacher_id' => $teacher?->id,
                'teacher_code_snapshot' => $data['teacher_code'] ?? null, 'requested_halaqa_id' => $halaqa?->id,
                'routing_mode' => $teacher ? 'specific_teacher' : 'all_available_teachers', 'state' => 'pending',
                'public_message' => $data['message'] ?? null, 'submitted_at' => now(),
            ]);
            $previous = $data['previous_memorization'] ?? [];
            RegistrationRequestProfile::create([
                'registration_request_id' => $request->id, 'gender' => $profile['gender'], 'birth_date' => $profile['birth_date'],
                'country' => $profile['country'], 'city' => $profile['city'], 'residence' => $profile['residence'] ?? null,
                'phone' => $profile['phone'], 'phone_zone' => $profile['phone_zone'], 'whatsapp_phone' => $profile['whatsapp_phone'] ?? null,
                'whatsapp_zone' => $profile['whatsapp_zone'] ?? null, 'memorization_level' => $profile['memorization_level'] ?? null,
                'review_level' => $profile['review_level'] ?? null,
                'memorized_juz_count' => $previous['memorized_juz_count'] ?? null,
                'memorized_surah_ids' => $previous['memorized_surah_ids'] ?? null,
                'last_completed_unit' => $previous['last_completed_unit'] ?? null,
                'previous_memorization_notes' => $previous['previous_teacher_notes'] ?? null,
                'stop_reasons' => $previous['stop_reasons'] ?? null,
                'profile_bio' => $profile['bio'] ?? null,
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
            $this->createFollowUpPlan($student, $data['follow_up_plan'], $attendance['timezone'], $request->id);

            $this->notifySubmitted($request->fresh(['student', 'teacher', 'profile']));

            return $request->load(['student.studentProfile.availability', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots']);
        });
    }

    public function accept(RegistrationRequest $registrationRequest, User $teacher): RegistrationRequest
    {
        return DB::transaction(function () use ($registrationRequest, $teacher): RegistrationRequest {
            $request = RegistrationRequest::query()->lockForUpdate()->with('requestedHalaqa')->findOrFail($registrationRequest->id);
            if (! in_array($request->state, ['pending', 'completion_requested'], true)) {
                throw new ApiConflictException('Only an open registration request can be accepted.', 'registration_state_conflict', 'registration_request', $request->id);
            }
            $halaqa = $request->requestedHalaqa;
            if ($halaqa && $halaqa->teacher_id !== $teacher->id) {
                throw new ApiConflictException('The teacher does not own the requested halaqa.', 'registration_target_forbidden', 'registration_request', $request->id);
            }
            if ($request->teacher_id !== null && $request->teacher_id !== $teacher->id) {
                throw new ApiConflictException('The request is assigned to another teacher.', 'registration_target_forbidden', 'registration_request', $request->id);
            }

            $request->forceFill(['teacher_id' => $teacher->id, 'state' => 'accepted', 'decided_by_teacher_id' => $teacher->id, 'decided_at' => now(), 'accepted_at' => now()])->save();
            $plan = FollowUpPlan::query()->where('source_registration_request_id', $request->id)->lockForUpdate()->first();
            if ($plan !== null) {
                FollowUpPlan::query()
                    ->where('student_id', $request->student_id)
                    ->where('status', 'active')
                    ->where('id', '!=', $plan->id)
                    ->update(['status' => 'archived']);
                $plan->forceFill(['status' => 'active', 'approved_by_user_id' => $teacher->id, 'approved_at' => now()])->save();
            }
            if ($halaqa && ! HalaqaMembership::query()->where('student_id', $request->student_id)->where('status', 'active')->exists()) {
                if ($halaqa->max_students !== null && $halaqa->activeMemberships()->count() >= $halaqa->max_students) {
                    throw new ApiConflictException('The halaqa has reached its student capacity.', 'halaqa_capacity_reached', 'halaqa', $halaqa->id);
                }
                HalaqaMembership::create(['id' => (string) Str::uuid(), 'halaqa_id' => $halaqa->id, 'student_id' => $request->student_id, 'status' => 'active', 'joined_at' => now()]);
            }

            $this->notifyStudent($request, 'accepted', 'Your registration request was accepted.');

            return $request->fresh(['student.studentProfile.availability', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots']);
        });
    }

    public function reject(RegistrationRequest $registrationRequest, User $teacher, ?string $note): RegistrationRequest
    {
        return $this->decide($registrationRequest, $teacher, 'rejected', $note);
    }

    public function requestCompletion(RegistrationRequest $registrationRequest, User $teacher, array $data): RegistrationRequest
    {
        $note = 'Required fields: '.implode(', ', $data['required_fields']).(! empty($data['note']) ? '. '.$data['note'] : '');

        return $this->decide($registrationRequest, $teacher, 'completion_requested', $note);
    }

    public function cancel(RegistrationRequest $registrationRequest): void
    {
        DB::transaction(function () use ($registrationRequest): void {
            $request = RegistrationRequest::query()->lockForUpdate()->findOrFail($registrationRequest->id);
            if (! in_array($request->state, ['pending', 'completion_requested'], true)) {
                throw new ApiConflictException('Only an open registration request can be cancelled.', 'registration_state_conflict', 'registration_request', $request->id);
            }
            $request->update(['state' => 'cancelled', 'withdrawn_at' => now()]);
        });
    }

    private function createFollowUpPlan(User $student, array $input, string $timezone, string $sourceRegistrationRequestId): void
    {
        $typeIds = TrackingType::query()->pluck('id', 'code');
        $unitIds = TrackingUnit::query()->pluck('id', 'code');
        foreach ($input['details'] as $detail) {
            if (! isset($typeIds[$detail['task_type']], $unitIds[$detail['unit']])) {
                throw new ApiConflictException('The follow-up plan contains an inactive tracking type or unit.', 'invalid_tracking_reference', 'follow_up_plan', $sourceRegistrationRequestId);
            }
        }

        $plan = FollowUpPlan::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'created_by_user_id' => $student->id,
            'source_registration_request_id' => $sourceRegistrationRequestId,
            'frequency' => $input['frequency'],
            'status' => 'draft',
            'timezone' => $timezone,
            'starts_on' => $input['starts_on'] ?? null,
            'ends_on' => $input['ends_on'] ?? null,
            'version' => 1,
        ]);
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

    private function notifySubmitted(RegistrationRequest $request): void
    {
        $request->loadMissing('profile');
        $teacherIds = $request->teacher_id !== null
            ? collect([$request->teacher_id])
            : User::query()->where('role', 'teacher')->where('status', 'active')
                ->where('gender', $request->profile?->gender)
                ->where('country', $request->profile?->country)
                ->pluck('id');

        foreach ($teacherIds as $teacherId) {
            $this->createNotification((string) $teacherId, 'submitted', $request, 'A new student registration request is available.');
        }
    }

    private function notifyStudent(RegistrationRequest $request, string $event, string $message): void
    {
        $this->createNotification((string) $request->student_id, $event, $request, $message);
    }

    private function createNotification(string $userId, string $event, RegistrationRequest $request, string $message): void
    {
        Notification::firstOrCreate(
            ['dedupe_key' => 'registration-request:'.$event.':'.$request->id.':'.$userId],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'type' => 'registration_request',
                'title' => 'Registration request update',
                'body' => $message,
                'payload' => ['entity_type' => 'registration_request', 'entity_id' => (string) $request->id, 'action' => 'view'],
            ],
        );
    }

    private function decide(RegistrationRequest $registrationRequest, User $teacher, string $state, ?string $note): RegistrationRequest
    {
        return DB::transaction(function () use ($registrationRequest, $teacher, $state, $note): RegistrationRequest {
            $request = RegistrationRequest::query()->lockForUpdate()->with('requestedHalaqa')->findOrFail($registrationRequest->id);
            if (! in_array($request->state, ['pending', 'completion_requested'], true)) {
                throw new ApiConflictException('Only an open registration request can be decided.', 'registration_state_conflict', 'registration_request', $request->id);
            }
            if (($request->teacher_id !== null && $request->teacher_id !== $teacher->id) || ($request->requestedHalaqa && $request->requestedHalaqa->teacher_id !== $teacher->id)) {
                throw new ApiConflictException('The teacher cannot decide this request.', 'registration_target_forbidden', 'registration_request', $request->id);
            }
            $request->update(['teacher_id' => $teacher->id, 'state' => $state, 'decision_note' => $note, 'decided_by_teacher_id' => $teacher->id, 'decided_at' => now()]);
            $message = $state === 'rejected' ? 'Your registration request was rejected.' : 'Your registration request needs additional information.';
            $this->notifyStudent($request, $state, $message);

            return $request->fresh(['student.studentProfile.availability', 'followUpPlan.details', 'followUpPlan.student.studentProfile.availability', 'teacher.teacherProfile', 'requestedHalaqa.teacher.teacherProfile', 'profile', 'availability.slots']);
        });
    }
}
