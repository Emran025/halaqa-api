<?php

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionReportTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $task = $this->resource;
        $evaluations = $task->relationLoaded('evaluations') ? $task->evaluations : collect();
        $detail = $task->relationLoaded('trackingDetail') ? $task->trackingDetail : null;

        return [
            'id' => (string) $task->id,
            'session_id' => (string) $task->session_id,
            'task_type' => $task->trackingType?->code,
            'sequence_no' => (int) $task->sequence_no,
            'state' => $task->state,
            'planned_from_unit_id' => $task->planned_from_unit_id,
            'planned_to_unit_id' => $task->planned_to_unit_id,
            'range' => null,
            'current_mushaf_state' => null,
            'planned_amount' => $task->planned_amount,
            'actual_amount' => $task->actual_amount,
            'comment' => $task->comment,
            'score' => $task->score,
            'gap' => $task->gap,
            'started_at' => $task->started_at?->toISOString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'teacher_evaluation' => $evaluations->firstWhere('evaluator_role', 'teacher')?->score,
            'student_evaluation' => $evaluations->firstWhere('evaluator_role', 'student')?->score,
            'mistakes_count' => $detail?->relationLoaded('mistakes') ? $detail->mistakes->count() : 0,
        ];
    }
}
