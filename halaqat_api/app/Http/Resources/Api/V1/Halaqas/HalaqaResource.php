<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HalaqaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $studentCount = $this->active_memberships_count ?? $this->activeMemberships()->count();
        $availableCapacity = $this->max_students === null
            ? null
            : max(0, $this->max_students - $studentCount);

        return [
            'id' => (string) $this->id,
            'teacher' => TeacherPublicSummaryResource::make($this->teacher),
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'student_count' => (int) $studentCount,
            'max_students' => $this->max_students,
            'available_capacity' => $availableCapacity,
            'gender' => $this->gender,
            'country' => $this->country,
            'residence' => $this->residence,
            'timezone' => $this->timezone,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
