<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SessionTaskService
{
    public function list(LiveSession $session, int $perPage = 20): LengthAwarePaginator
    {
        return SessionTask::query()->where('session_id', $session->id)->with(['trackingType', 'trackingDetail.mistakes'])->orderBy('sequence_no')->paginate($perPage);
    }

    public function get(LiveSession $session, SessionTask $task): SessionTask
    {
        $this->assertBelongsToSession($session, $task);

        return $task->load(['trackingType', 'trackingDetail.mistakes']);
    }

    public function update(User $actor, LiveSession $session, SessionTask $task, array $data): SessionTask
    {
        $this->assertBelongsToSession($session, $task);
        if ($actor->isStudent() && array_diff(array_keys($data), ['current_page', 'current_ayah_id']) !== []) {
            throw new ApiConflictException('A student may update only the current Quran position.', 'task_update_not_allowed', 'task', $task->id);
        }

        $fields = array_intersect_key($data, array_flip(['planned_from_unit_id', 'planned_to_unit_id', 'start_page', 'start_ayah_id', 'end_page', 'end_ayah_id', 'current_page', 'current_ayah_id', 'planned_amount', 'actual_amount']));
        if (array_key_exists('state', $data)) {
            $fields['state'] = $data['state'];
            if ($data['state'] === 'in_progress' && $task->started_at === null) {
                $fields['started_at'] = now();
            }
            if (in_array($data['state'], ['completed', 'skipped', 'cancelled'], true) && $task->completed_at === null) {
                $fields['completed_at'] = now();
            }
        }
        DB::transaction(function () use ($task, $fields): void {
            $task->update($fields);
            $detailFields = array_intersect_key($fields, array_flip(['actual_amount', 'comment', 'score', 'gap']));
            if (array_key_exists('state', $fields)) {
                $detailFields['state'] = $fields['state'] === 'skipped' ? 'cancelled' : $fields['state'];
            }
            if ($detailFields !== []) {
                $task->trackingDetail()->update($detailFields);
            }
        });

        return $task->fresh(['trackingType', 'trackingDetail.mistakes']);
    }

    public function saveDraft(User $actor, LiveSession $session, SessionTask $task, array $data): SessionTask
    {
        $this->assertBelongsToSession($session, $task);
        if ((string) $task->last_draft_operation_id === (string) $data['client_operation_id']) {
            return $task->load(['trackingType', 'trackingDetail.mistakes']);
        }

        $task->update(['current_page' => $data['current_page'] ?? null, 'current_ayah_id' => $data['current_ayah_id'] ?? null, 'last_draft_operation_id' => $data['client_operation_id']]);

        return $task->fresh(['trackingType', 'trackingDetail.mistakes']);
    }

    private function assertBelongsToSession(LiveSession $session, SessionTask $task): void
    {
        if ((string) $task->session_id !== (string) $session->id) {
            throw new ApiConflictException('The task does not belong to this session.', 'task_session_mismatch', 'task', $task->id);
        }
    }
}
