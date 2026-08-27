<?php

namespace App\Services\Progress;

use App\Models\LiveSession;
use App\Models\Mistake;
use App\Models\QuranEdition;
use App\Models\SessionTask;
use App\Models\User;

class ProgressQueryService
{
    /** @return array<string, mixed> */
    public function forStudent(User $student, ?string $taskType = null): array
    {
        $sessions = LiveSession::query()->where('student_id', $student->id);
        $tasks = SessionTask::query()
            ->whereHas('session', fn ($session) => $session->where('student_id', $student->id))
            ->when($taskType !== null, fn ($query) => $query->whereHas('trackingType', fn ($type) => $type->where('code', $taskType)));

        $totalTasks = (clone $tasks)->count();
        $totalMistakes = Mistake::query()
            ->whereHas('detail.task.session', function ($session) use ($student, $taskType): void {
                $session->where('student_id', $student->id);
                if ($taskType !== null) {
                    $session->whereHas('taskType', fn ($type) => $type->where('code', $taskType));
                }
            })
            ->count();

        $typeTotals = [];
        foreach (['memorization', 'review', 'recitation'] as $type) {
            $typeTotals[$type] = $taskType !== null && $taskType !== $type
                ? 0
                : (clone $tasks)->whereHas('trackingType', fn ($trackingType) => $trackingType->where('code', $type))->count();
        }

        $lastCompleted = [];
        $editionId = (int) (QuranEdition::query()->where('is_default', true)->value('id') ?? 1);
        foreach (['memorization', 'review', 'recitation'] as $type) {
            $task = (clone $tasks)
                ->whereHas('trackingType', fn ($trackingType) => $trackingType->where('code', $type))
                ->with('trackingType')
                ->latest('completed_at')
                ->first();
            $lastCompleted[$type] = $task === null ? null : [
                'edition_id' => $editionId,
                'start_page' => $task->start_page,
                'start_ayah_id' => $task->start_ayah_id,
                'end_page' => $task->end_page,
                'end_ayah_id' => $task->end_ayah_id,
                'end_ayah_number' => null,
            ];
        }

        return [
            'student_id' => (string) $student->id,
            'last_completed' => $lastCompleted,
            'totals' => [
                'total_sessions' => (clone $sessions)->where('state', 'ended')->count(),
                'total_tasks' => $totalTasks,
                'total_mistakes' => $totalMistakes,
                'memorization_tasks' => $typeTotals['memorization'],
                'review_tasks' => $typeTotals['review'],
                'recitation_tasks' => $typeTotals['recitation'],
            ],
        ];
    }
}
