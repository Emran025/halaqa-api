<?php

namespace App\Services\Sessions;

use App\Events\LiveSession\LiveSessionRealtimeEvent;
use App\Events\Notifications\SessionEnded;
use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Models\User;
use App\Realtime\RealtimeEventTypes;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\DB;

class SessionTransitionService
{
    public function accept(LiveSession $session): LiveSession
    {
        $updated = $this->transition($session, ['requested'], ['state' => 'accepted', 'accepted_at' => now()]);
        event(new LiveSessionRealtimeEvent($updated, RealtimeEventTypes::SESSION_ACCEPTED));

        return $updated;
    }

    public function reject(LiveSession $session, ?string $note = null): LiveSession
    {
        $updated = $this->transition($session, ['requested'], ['state' => 'rejected', 'end_reason' => $note, 'ended_at' => now()]);
        event(new LiveSessionRealtimeEvent($updated, RealtimeEventTypes::SESSION_REJECTED));

        return $updated;
    }

    public function cancel(LiveSession $session): void
    {
        $updated = $this->transition($session, ['requested'], ['state' => 'cancelled', 'ended_at' => now()]);
        event(new LiveSessionRealtimeEvent($updated, RealtimeEventTypes::SESSION_STATE_CHANGED));
    }

    public function leave(LiveSession $session): LiveSession
    {
        $ended = $this->transition($session, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'], ['state' => 'ended', 'end_reason' => 'participant_left', 'ended_at' => now()]);
        app(SessionReportService::class)->ensureForEndedSession($ended);
        event(new SessionEnded($ended));
        event(new LiveSessionRealtimeEvent($ended, RealtimeEventTypes::SESSION_ENDED));

        return $ended;
    }

    public function end(LiveSession $session, ?string $reason = null): LiveSession
    {
        $ended = $this->transition($session, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'], ['state' => 'ended', 'end_reason' => $reason, 'ended_at' => now()]);
        app(SessionReportService::class)->ensureForEndedSession($ended);
        event(new SessionEnded($ended));
        event(new LiveSessionRealtimeEvent($ended, RealtimeEventTypes::SESSION_ENDED));

        return $ended;
    }

    public function markDirectConnectionUnavailable(User $actor, LiveSession $session, string $reason, string $operationId): LiveSession
    {
        $replayed = false;
        $updated = DB::transaction(function () use ($actor, $session, $reason, $operationId, &$replayed): LiveSession {
            $locked = LiveSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            $existing = LiveSession::query()->where('last_client_operation_id', $operationId)->lockForUpdate()->first();
            if ($existing !== null) {
                if ((string) $existing->id !== (string) $locked->id || (string) $existing->last_operation_by_user_id !== (string) $actor->id || $existing->last_operation_type !== 'direct_connection_unavailable') {
                    throw new ApiConflictException('The client operation id is already used by another session transition.', 'idempotency_key_reused', 'client_operation_id', $operationId);
                }
                $replayed = true;

                return $locked->fresh(['teacher', 'student', 'taskType']);
            }
            if (! in_array($locked->state, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'], true)) {
                throw new ApiConflictException('The session cannot mark direct connection unavailable from its current state.', 'invalid_direct_connection_state', 'session', (string) $locked->id);
            }
            $locked->update(['state' => 'direct_connection_unavailable', 'end_reason' => $reason, 'last_client_operation_id' => $operationId, 'last_operation_by_user_id' => $actor->id, 'last_operation_type' => 'direct_connection_unavailable']);

            return $locked->fresh(['teacher', 'student', 'taskType']);
        });

        if (! $replayed) {
            event(new LiveSessionRealtimeEvent($updated, RealtimeEventTypes::DIRECT_CONNECTION_UNAVAILABLE));
        }

        return $updated;
    }

    private function transition(LiveSession $session, array $fromStates, array $changes): LiveSession
    {
        return DB::transaction(function () use ($session, $fromStates, $changes): LiveSession {
            $locked = LiveSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->state, $fromStates, true)) {
                throw new ApiConflictException('The session cannot make this state transition.', 'invalid_session_transition', 'session', $session->id);
            }
            $locked->update($changes);

            return $locked->fresh(['teacher', 'student', 'taskType']);
        });
    }
}
