<?php

namespace App\Services\Sessions;

use App\Exceptions\ApiConflictException;
use App\Models\HalaqaMembership;
use App\Models\LiveSession;
use App\Models\TrackingType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveSessionService
{
    public function create(User $teacher, array $data): LiveSession
    {
        return DB::transaction(function () use ($teacher, $data): LiveSession {
            $membership = HalaqaMembership::query()->where('halaqa_id', $data['halaqa_id'])->where('student_id', $data['student_id'])->where('status', 'active')->whereHas('halaqa', fn ($q) => $q->where('teacher_id', $teacher->id))->first();
            if ($membership === null) {
                throw new ApiConflictException('The student is not an active member of this teacher\'s halaqa.', 'student_not_in_halaqa', 'student', $data['student_id']);
            }
            $activeStates = ['requested', 'accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'];
            if (LiveSession::query()->where('student_id', $data['student_id'])->whereIn('state', $activeStates)->lockForUpdate()->exists()) {
                throw new ApiConflictException('The student already has an active live session.', 'active_session_exists', 'student', $data['student_id']);
            }
            $typeId = TrackingType::query()->where('code', $data['task_type'])->value('id');

            return LiveSession::create(['id' => (string) Str::uuid(), 'halaqa_id' => $data['halaqa_id'], 'teacher_id' => $teacher->id, 'student_id' => $data['student_id'], 'follow_up_item_id' => $data['follow_up_item_id'] ?? null, 'task_type_id' => $typeId, 'state' => 'requested', 'scheduled_at' => $data['scheduled_at'] ?? null, 'requested_at' => now(), 'direct_p2p_only' => true])->load(['teacher', 'student', 'taskType']);
        });
    }
}
