<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendancePreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'timezone' => $this->timezone,
            'preferred_session_duration_minutes' => (int) $this->preferred_session_duration_minutes,
            'weekly_slots' => $this->slots->map(fn ($slot) => [
                'day_of_week' => (int) $slot->day_of_week,
                'from' => substr((string) $slot->available_from, 0, 5),
                'to' => substr((string) $slot->available_to, 0, 5),
                'preferred' => (bool) $slot->is_preferred,
            ])->values()->all(),
        ];
    }
}
