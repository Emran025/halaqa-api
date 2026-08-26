<?php

namespace App\Http\Resources\Api\V1\Registrations;

use App\Http\Resources\Api\V1\Halaqas\HalaqaPublicSummaryResource;
use App\Http\Resources\Api\V1\Halaqas\TeacherPublicSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isStudent = $viewer?->id === $this->student_id;
        $isAcceptedTeacher = $viewer?->isTeacher() && $this->state === 'accepted' && $this->teacher_id === $viewer->id;
        $fullDetailsVisible = $isStudent || $isAcceptedTeacher;
        $profile = $this->profile;

        return [
            'id' => (string) $this->id,
            'requested_teacher' => $this->teacher ? TeacherPublicSummaryResource::make($this->teacher) : null,
            'requested_halaqa' => $this->requestedHalaqa ? HalaqaPublicSummaryResource::make($this->requestedHalaqa) : null,
            'routing_mode' => $this->routing_mode,
            'teacher_code' => $this->teacher_code_snapshot,
            'student_summary' => ApplicantPublicSummaryResource::make($this),
            'profile' => $fullDetailsVisible && $profile ? [
                'gender' => $profile->gender, 'birth_date' => $profile->birth_date?->format('Y-m-d'), 'country' => $profile->country,
                'city' => $profile->city, 'residence' => $profile->residence, 'phone' => $profile->phone, 'phone_zone' => $profile->phone_zone,
                'whatsapp_phone' => $profile->whatsapp_phone, 'whatsapp_zone' => $profile->whatsapp_zone,
                'memorization_level' => $profile->memorization_level, 'review_level' => $profile->review_level, 'bio' => $profile->profile_bio,
            ] : null,
            'previous_memorization' => $fullDetailsVisible && $profile ? [
                'memorization_level' => $profile->memorization_level, 'review_level' => $profile->review_level,
                'memorized_juz_count' => $profile->memorized_juz_count,
                'previous_teacher_notes' => $profile->previous_memorization_notes,
                'stop_reasons' => $profile->stop_reasons,
                'memorized_surah_ids' => $profile->memorized_surah_ids ?? [],
                'last_completed_unit' => $profile->last_completed_unit,
            ] : null,
            'attendance_preferences' => $fullDetailsVisible ? $this->student?->studentProfile?->availability : null,
            'follow_up_plan' => $fullDetailsVisible ? $this->student?->studentProfile?->followUpPlan : null,
            'state' => $this->state,
            'visibility' => $isStudent ? 'student_visible' : ($isAcceptedTeacher ? 'relationship_visible' : 'public_summary'),
            'message' => $this->public_message,
            'decision_note' => $fullDetailsVisible ? $this->decision_note : null,
            'decided_at' => $this->decided_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
