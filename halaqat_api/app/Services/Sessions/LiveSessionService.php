<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\DailyTracking;
use App\Models\HalaqaMembership;
use App\Models\LiveSession;
use App\Models\SessionTask;
use App\Models\TrackingDetail;
use App\Models\TrackingType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveSessionService
{
    public function createTask(User $teacher, LiveSession $session, array $data): SessionTask
    {
        return DB::transaction(function () use ($teacher, $session, $data): SessionTask {
            if ($session->teacher_id !== $teacher->id || ! in_array($session->state, ['requested', 'accepted', 'connecting', 'connected'], true)) {
                throw new ApiConflictException('The session is not managed by this teacher or is not available.', 'session_not_manageable', 'session', $session->id);
            }

            $existingOperation = SessionTask::query()->where('client_operation_id', $data['client_operation_id'])->first();
            if ($existingOperation !== null) {
                if ((string) $existingOperation->session_id !== (string) $session->id) {
                    throw new ApiConflictException('The client operation already belongs to another task.', 'idempotency_key_reused', 'client_operation_id', $data['client_operation_id']);
                }

                return $existingOperation->load(['trackingType', 'trackingDetail']);
            }

            $sequence = $data['sequence_no'] ?? ((int) $session->tasks()->max('sequence_no') + 1);
            if ($session->tasks()->where('sequence_no', $sequence)->exists()) {
                throw new ApiConflictException('The task sequence already exists for this session.', 'task_sequence_exists', 'session', $session->id);
            }
            $typeId = TrackingType::query()->where('code', $data['task_type'])->value('id');
            if ($typeId === null) {
                throw new ApiConflictException('The tracking type is not available.', 'tracking_type_not_found', 'task_type', $data['task_type']);
            }

            $task = SessionTask::create(['id' => (string) Str::uuid(), 'session_id' => $session->id, 'client_operation_id' => $data['client_operation_id'], 'tracking_type_id' => $typeId, 'sequence_no' => $sequence, 'planned_amount' => $data['planned_amount'] ?? null, 'planned_from_unit_id' => $data['planned_from_unit_id'] ?? null, 'planned_to_unit_id' => $data['planned_to_unit_id'] ?? null, 'start_page' => $data['start_page'] ?? null, 'start_ayah_id' => $data['start_ayah_id'] ?? null, 'end_page' => $data['end_page'] ?? null, 'end_ayah_id' => $data['end_ayah_id'] ?? null, 'state' => 'draft']);
            $membership = HalaqaMembership::query()->where('halaqa_id', $session->halaqa_id)->where('student_id', $session->student_id)->where('status', 'active')->first();
            if ($membership === null) {
                throw new ApiConflictException('An active membership is required for tracking this task.', 'membership_required', 'session', $session->id);
            }
            $tracking = DailyTracking::query()->firstOrCreate(['student_id' => $session->student_id, 'date' => $session->scheduled_at?->toDateString() ?? now()->toDateString()], ['id' => (string) Str::uuid(), 'membership_id' => $membership->id, 'attendance_type' => 'present']);
            TrackingDetail::query()->firstOrCreate(['session_task_id' => $task->id], ['uuid' => (string) Str::uuid(), 'tracking_id' => $tracking->id, 'tracking_type_id' => $typeId, 'from_unit_id' => $data['planned_from_unit_id'] ?? null, 'to_unit_id' => $data['planned_to_unit_id'] ?? null, 'actual_amount' => 0, 'state' => 'draft']);

            return $task->load(['trackingType', 'trackingDetail']);
        });
    }

    public function create(User $teacher, array $data): LiveSession
    {
        return DB::transaction(function () use ($teacher, $data): LiveSession {
            $existingOperation = LiveSession::query()->where('client_operation_id', $data['client_operation_id'])->first();
            if ($existingOperation !== null) {
                if ((string) $existingOperation->teacher_id !== (string) $teacher->id || (string) $existingOperation->student_id !== (string) $data['student_id'] || (string) $existingOperation->halaqa_id !== (string) $data['halaqa_id']) {
                    throw new ApiConflictException('The client operation already belongs to another session.', 'idempotency_key_reused', 'client_operation_id', $data['client_operation_id']);
                }

                return $existingOperation->load(['teacher', 'student', 'taskType']);
            }

            $membership = HalaqaMembership::query()->where('halaqa_id', $data['halaqa_id'])->where('student_id', $data['student_id'])->where('status', 'active')->whereHas('halaqa', fn ($q) => $q->where('teacher_id', $teacher->id))->first();
            if ($membership === null) {
                throw new ApiConflictException('The student is not an active member of this teacher\'s halaqa.', 'student_not_in_halaqa', 'student', $data['student_id']);
            }
            $activeStates = ['requested', 'accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'];
            if (LiveSession::query()->where('student_id', $data['student_id'])->whereIn('state', $activeStates)->lockForUpdate()->exists()) {
                throw new ApiConflictException('The student already has an active live session.', 'active_session_exists', 'student', $data['student_id']);
            }
            $typeId = TrackingType::query()->where('code', $data['task_type'])->value('id');
            if ($typeId === null) {
                throw new ApiConflictException('The tracking type is not available.', 'tracking_type_not_found', 'task_type', $data['task_type']);
            }

            return LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $data['halaqa_id'], 'teacher_id' => $teacher->id, 'student_id' => $data['student_id'], 'follow_up_item_id' => $data['follow_up_item_id'] ?? null, 'task_type_id' => $typeId, 'state' => 'requested', 'scheduled_at' => $data['scheduled_at'] ?? null, 'requested_at' => now(), 'direct_p2p_only' => true, 'client_operation_id' => $data['client_operation_id']])->load(['teacher', 'student', 'taskType']);
        });
    }
}
