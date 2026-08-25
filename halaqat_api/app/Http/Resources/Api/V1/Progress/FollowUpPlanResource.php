<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'student_id' => (string) $this->student_id,
            'created_by_user_id' => (string) $this->created_by_user_id,
            'source_registration_request_id' => $this->source_registration_request_id,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'details' => $this->details->map(fn ($detail) => [
                'id' => (string) $detail->id,
                'task_type' => $detail->trackingType?->code,
                'unit' => $detail->trackingUnit?->code,
                'amount' => (float) $detail->amount,
                'notes' => $detail->notes,
                'sort_order' => (int) $detail->sort_order,
                'created_at' => $detail->created_at?->toISOString(),
                'updated_at' => $detail->updated_at?->toISOString(),
            ])->values()->all(),
            'attendance_preferences' => AttendancePreferencesResource::make($this->student->studentProfile->availability),
            'starts_on' => $this->starts_on?->format('Y-m-d'),
            'ends_on' => $this->ends_on?->format('Y-m-d'),
            'version' => (int) $this->version,
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
