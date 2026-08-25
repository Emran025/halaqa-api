<?php

namespace App\Http\Resources\Api\V1\Profile;

use App\Http\Resources\Api\V1\Progress\AttendancePreferencesResource;
use App\Http\Resources\Api\V1\Progress\FollowUpPlanResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $profile = $this->studentProfile;
        $isSelf = $request->user()?->id === $this->id;

        return [
            'id' => (string) $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'country' => $this->country,
            'city' => $this->city,
            'residence' => $this->residence,
            'phone' => $this->phone,
            'phone_zone' => $this->phone_zone,
            'whatsapp_phone' => $this->whatsapp_phone,
            'whatsapp_zone' => $this->whatsapp_zone,
            'memorization_level' => $profile?->memorization_level,
            'review_level' => $profile?->review_level,
            'previous_memorization' => [
                'memorized_juz_count' => $profile?->memorized_juz_count !== null ? (float) $profile->memorized_juz_count : null,
                'previous_teacher_notes' => $profile?->previous_memorization_notes,
                'stop_reasons' => $profile?->stop_reasons,
                'memorized_surah_ids' => $profile?->memorized_surah_ids ?? [],
                'last_completed_unit' => $profile?->last_completed_unit,
            ],
            'attendance_preferences' => $profile?->availability
                ? new AttendancePreferencesResource($profile->availability)
                : null,
            'follow_up_plan' => $profile?->followUpPlan
                ? new FollowUpPlanResource($profile->followUpPlan)
                : null,
            'visibility' => $isSelf ? 'self' : 'relationship_visible',
        ];
    }
}
