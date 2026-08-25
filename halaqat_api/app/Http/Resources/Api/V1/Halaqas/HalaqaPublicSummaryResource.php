<?php

namespace App\Http\Resources\Api\V1\Halaqas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HalaqaPublicSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $count = $this->active_memberships_count ?? $this->activeMemberships()->count();

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'country' => $this->country,
            'residence' => $this->residence,
            'status' => $this->status,
            'student_count' => (int) $count,
            'max_students' => $this->max_students,
            'available_capacity' => $this->max_students === null ? null : max(0, $this->max_students - $count),
            'timezone' => $this->timezone,
        ];
    }
}
