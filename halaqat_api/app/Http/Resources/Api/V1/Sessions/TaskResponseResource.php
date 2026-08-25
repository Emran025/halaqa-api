<?php

namespace App\Http\Resources\Api\V1\Sessions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $task = $this->resource;

        return ['task' => ['id' => (string) $task->id, 'session_id' => (string) $task->session_id, 'task_type' => $task->trackingType->code, 'sequence_no' => (int) $task->sequence_no, 'state' => $task->state, 'planned_from_unit_id' => $task->planned_from_unit_id, 'planned_to_unit_id' => $task->planned_to_unit_id, 'planned_amount' => $task->planned_amount, 'actual_amount' => $task->actual_amount, 'comment' => $task->comment, 'score' => $task->score, 'gap' => $task->gap, 'started_at' => $task->started_at?->toISOString(), 'completed_at' => $task->completed_at?->toISOString(), 'mistakes_count' => $task->relationLoaded('trackingDetail') && $task->trackingDetail?->relationLoaded('mistakes') ? $task->trackingDetail->mistakes->count() : 0]];
    }
}
