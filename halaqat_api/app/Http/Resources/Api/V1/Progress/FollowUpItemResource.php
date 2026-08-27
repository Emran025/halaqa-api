<?php

namespace App\Http\Resources\Api\V1\Progress;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (string) $this->id, 'plan_id' => (string) $this->plan_id, 'plan_detail_id' => (string) $this->plan_detail_id, 'student_id' => (string) $this->student_id, 'halaqa_id' => $this->halaqa_id === null ? null : (string) $this->halaqa_id, 'task_type' => $this->detail?->trackingType?->code, 'plan_detail' => ['id' => (string) $this->detail->id, 'task_type' => $this->detail->trackingType?->code, 'unit' => $this->detail->trackingUnit?->code, 'amount' => (float) $this->detail->amount, 'notes' => $this->detail->notes, 'sort_order' => (int) $this->detail->sort_order, 'created_at' => $this->detail->created_at?->toISOString(), 'updated_at' => $this->detail->updated_at?->toISOString()], 'scheduled_for' => $this->scheduled_for?->toISOString(), 'timezone' => (string) $this->timezone, 'state' => $this->state, 'completed_at' => $this->completed_at?->toISOString(), 'skipped_at' => $this->skipped_at?->toISOString(), 'skip_reason' => $this->skip_reason, 'rescheduled_from_id' => $this->rescheduled_from_id === null ? null : (string) $this->rescheduled_from_id, 'notification_sent_at' => $this->notification_sent_at?->toISOString(), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
