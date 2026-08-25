<?php

namespace App\Services\Reports;

use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\SessionReport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionReportService
{
    public function get(LiveSession $session): SessionReport
    {
        return SessionReport::query()
            ->where('session_id', $session->id)
            ->with($this->reportRelations())
            ->firstOrFail();
    }

    public function listForStudent(User $student, array $filters): LengthAwarePaginator
    {
        return SessionReport::query()
            ->whereHas('session', fn ($query) => $query->where('student_id', $student->id))
            ->with($this->reportRelations())
            ->when($filters['task_type'] ?? null, fn ($query, $taskType) => $query->whereHas('session.taskType', fn ($type) => $type->where('code', $taskType)))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereHas('session', fn ($session) => $session->whereDate('ended_at', '>=', $from)))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereHas('session', fn ($session) => $session->whereDate('ended_at', '<=', $to)))
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 25), ['*'], 'page', (int) ($filters['page'] ?? 1));
    }

    public function ensureForEndedSession(LiveSession $session): SessionReport
    {
        return DB::transaction(function () use ($session): SessionReport {
            $lockedSession = LiveSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($lockedSession->state !== 'ended') {
                throw new ApiConflictException('A report can only be generated for an ended session.', 'report_session_not_ended', 'session', (string) $lockedSession->id);
            }
            $report = SessionReport::query()->where('session_id', $lockedSession->id)->lockForUpdate()->first();
            if ($report === null) {
                $report = SessionReport::create(['id' => (string) Str::uuid(), 'session_id' => $lockedSession->id, 'state' => 'draft', 'version' => 1]);
            }
            $this->refreshMetrics($report, $lockedSession);

            return $report->fresh($this->reportRelations());
        });
    }

    public function update(User $actor, SessionReport $report, array $data): SessionReport
    {
        return DB::transaction(function () use ($report, $data): SessionReport {
            $locked = $this->lock($report);
            if (! in_array($locked->state, ['draft', 'reopened'], true)) {
                throw new ApiConflictException('Only a draft or reopened report can be edited.', 'report_state_conflict', 'report', (string) $locked->id);
            }
            $locked->update(['summary' => $data['summary'] ?? null, 'version' => $locked->version + 1]);

            return $this->load($locked);
        });
    }

    public function approve(User $actor, SessionReport $report, ?string $note, string $operationId): SessionReport
    {
        return DB::transaction(function () use ($actor, $report, $note, $operationId): SessionReport {
            $locked = $this->lock($report);
            $replay = $this->assertOperation($locked, $actor, $operationId, 'approve');
            if ($replay !== null) {
                return $this->load($replay);
            }
            if (! in_array($locked->state, ['draft', 'reopened'], true)) {
                throw new ApiConflictException('The report cannot be approved in its current state.', 'report_state_conflict', 'report', (string) $locked->id);
            }
            $session = $locked->session()->first();
            if ($session?->state !== 'ended') {
                throw new ApiConflictException('Only an ended session can have an approved report.', 'report_session_not_ended', 'report', (string) $locked->id);
            }
            $locked->update(['state' => 'pending_student_acknowledgment', 'teacher_approved_by' => $actor->id, 'teacher_approved_at' => now(), 'teacher_approval_note' => $note, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'approve', 'version' => $locked->version + 1]);

            return $this->load($locked);
        });
    }

    public function acknowledge(User $actor, SessionReport $report, string $action, ?string $note, string $operationId): SessionReport
    {
        return DB::transaction(function () use ($actor, $report, $action, $note, $operationId): SessionReport {
            $locked = $this->lock($report);
            $replay = $this->assertOperation($locked, $actor, $operationId, 'acknowledge_'.$action);
            if ($replay !== null) {
                return $this->load($replay);
            }
            if ($locked->state !== 'pending_student_acknowledgment') {
                throw new ApiConflictException('The report is not waiting for student acknowledgment.', 'report_state_conflict', 'report', (string) $locked->id);
            }
            $changes = ['student_acknowledged_at' => now(), 'student_acknowledgment_note' => $note ?? $locked->student_acknowledgment_note, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'acknowledge_'.$action, 'version' => $locked->version + 1];
            if ($action === 'acknowledge') {
                $changes['state'] = 'completed';
            }
            $locked->update($changes);

            return $this->load($locked);
        });
    }

    public function reopen(User $actor, SessionReport $report, string $reason, string $operationId): SessionReport
    {
        return DB::transaction(function () use ($actor, $report, $reason, $operationId): SessionReport {
            $locked = $this->lock($report);
            $replay = $this->assertOperation($locked, $actor, $operationId, 'reopen');
            if ($replay !== null) {
                return $this->load($replay);
            }
            if ($locked->state !== 'completed') {
                throw new ApiConflictException('Only a completed report can be reopened.', 'report_state_conflict', 'report', (string) $locked->id);
            }
            $locked->update(['state' => 'reopened', 'teacher_approved_by' => null, 'teacher_approved_at' => null, 'teacher_approval_note' => null, 'student_acknowledged_at' => null, 'student_acknowledgment_note' => null, 'reopened_by' => $actor->id, 'reopened_at' => now(), 'reopen_reason' => $reason, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'reopen', 'version' => $locked->version + 1]);

            return $this->load($locked);
        });
    }

    private function refreshMetrics(SessionReport $report, LiveSession $session): void
    {
        $tasks = $session->tasks()->with(['trackingType', 'trackingDetail.mistakes.mistakeType', 'evaluations'])->get();
        $mistakes = $tasks->flatMap(fn ($task) => $task->trackingDetail?->mistakes ?? collect());
        $counts = $mistakes->groupBy(fn ($mistake) => $mistake->mistakeType?->code ?? 'none')->map(fn ($items, $code) => ['mistake_type' => $code, 'count' => $items->count()])->values()->all();
        $duration = $session->connected_at !== null && $session->ended_at !== null ? $session->connected_at->diffInSeconds($session->ended_at) : null;
        $report->update(['duration_seconds' => $duration, 'total_tasks' => $tasks->count(), 'total_mistakes' => $mistakes->count(), 'mistake_counts' => $counts]);
    }

    private function lock(SessionReport $report): SessionReport
    {
        return SessionReport::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
    }

    private function load(SessionReport $report): SessionReport
    {
        return $report->fresh($this->reportRelations());
    }

    private function assertOperation(SessionReport $report, User $actor, string $operationId, string $type): ?SessionReport
    {
        $existing = SessionReport::query()->where('last_client_operation_id', $operationId)->first();
        if ($existing === null) {
            return null;
        }
        if ((string) $existing->id !== (string) $report->id || (string) $existing->last_operation_by_user_id !== (string) $actor->id || $existing->last_operation_type !== $type) {
            throw new ApiConflictException('The client operation id is already used by another report operation.', 'idempotency_key_reused', 'client_operation_id', $operationId);
        }

        return $existing;
    }

    /** @return list<string> */
    private function reportRelations(): array
    {
        return ['session.teacher', 'session.student', 'session.taskType', 'session.tasks.trackingType', 'session.tasks.trackingDetail.mistakes', 'session.tasks.evaluations'];
    }
}
