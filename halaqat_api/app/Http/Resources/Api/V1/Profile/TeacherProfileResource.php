<?php

namespace App\Http\Resources\Api\V1\Profile;

use App\Http\Resources\Api\V1\Halaqas\HalaqaPublicSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $profile = $this->teacherProfile;
        $activeHalaqaCount = (int) ($this->active_halaqas_count ?? 0);
        $maximum = $profile?->max_halaqas;

        return [
            'id' => (string) $this->id,
            'role' => $this->role,
            'display_name' => $this->name,
            'teacher_code' => $profile?->teacher_code,
            'avatar' => $this->avatar_path,
            'gender' => $this->gender,
            'country' => $this->country,
            'city' => $this->city,
            'qualification' => $profile?->qualification,
            'experience_years' => (int) ($profile?->experience_years ?? 0),
            'capacity_available' => $maximum === null || $maximum === 0 || $activeHalaqaCount < $maximum,
            'bio' => $profile?->bio,
            'active_halaqa_count' => (int) $activeHalaqaCount,
            'public_halaqas' => HalaqaPublicSummaryResource::collection($this->whenLoaded('halaqas')),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_zone' => $this->phone_zone,
            'whatsapp_phone' => $this->whatsapp_phone,
            'whatsapp_zone' => $this->whatsapp_zone,
            'residence' => $this->residence,
            'available_time' => $profile?->available_time,
            'documents' => TeacherDocumentResource::collection($profile?->documents ?? collect()),
        ];
    }
}
