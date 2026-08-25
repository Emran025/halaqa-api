<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherPublicSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->teacherProfile;
        $activeHalaqaCount = $this->active_halaqas_count ?? $this->halaqas()->where('status', 'active')->count();
        $maximum = (int) ($profile?->max_halaqas ?? 0);

        return [
            'id' => (string) $this->id,
            'display_name' => $this->name,
            'teacher_code' => $profile?->teacher_code,
            'avatar' => $this->avatar_path,
            'gender' => $this->gender,
            'country' => $this->country,
            'city' => $this->city,
            'qualification' => $profile?->qualification,
            'experience_years' => (int) ($profile?->experience_years ?? 0),
            'capacity_available' => $maximum === 0 || $activeHalaqaCount < $maximum,
        ];
    }
}
