<?php

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $report = $this->resource;
        $teacherApproved = $report->teacher_approved_at !== null;
        $studentAcknowledged = $report->student_acknowledged_at !== null;

        return [
            'id' => (string) $report->id,
            'session_id' => (string) $report->session_id,
            'state' => $report->state,
            'summary' => $report->summary,
            'duration_seconds' => $report->duration_seconds,
            'total_tasks' => (int) $report->total_tasks,
            'total_mistakes' => (int) $report->total_mistakes,
            'mistake_counts' => $report->mistake_counts ?? [],
            'version' => (int) $report->version,
            'tasks' => $report->relationLoaded('session') && $report->session?->relationLoaded('tasks')
                ? $report->session->tasks->map(fn ($task) => (new SessionReportTaskResource($task))->resolve($request))->values()->all()
                : [],
            'teacher_approval' => [
                'status' => $teacherApproved ? 'approved' : 'pending',
                'approved_by' => $report->teacher_approved_by === null ? null : (string) $report->teacher_approved_by,
                'approved_at' => $report->teacher_approved_at?->toISOString(),
                'note' => $report->teacher_approval_note,
            ],
            'student_acknowledgment' => [
                'status' => $studentAcknowledged ? ($report->student_acknowledgment_note === null ? 'acknowledged' : 'comment_submitted') : 'pending',
                'acknowledged_by' => $studentAcknowledged ? (string) $report->session->student_id : null,
                'acknowledged_at' => $report->student_acknowledged_at?->toISOString(),
                'note' => $report->student_acknowledgment_note,
            ],
            'reopened_by' => $report->reopened_by === null ? null : (string) $report->reopened_by,
            'reopened_at' => $report->reopened_at?->toISOString(),
            'reopen_reason' => $report->reopen_reason,
            'created_at' => $report->created_at?->toISOString(),
            'updated_at' => $report->updated_at?->toISOString(),
        ];
    }
}
