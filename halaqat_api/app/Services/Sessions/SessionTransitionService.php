<?php

namespace App\Services\Sessions;

use App\Events\Notifications\SessionEnded;
use App\Exceptions\ApiConflictException;
use App\Models\LiveSession;
use App\Services\Reports\SessionReportService;
use Illuminate\Support\Facades\DB;

class SessionTransitionService
{
    public function accept(LiveSession $session): LiveSession
    {
        return $this->transition($session, ['requested'], ['state' => 'accepted', 'accepted_at' => now()]);
    }

    public function reject(LiveSession $session, ?string $note = null): LiveSession
    {
        return $this->transition($session, ['requested'], ['state' => 'rejected', 'end_reason' => $note, 'ended_at' => now()]);
    }

    public function cancel(LiveSession $session): void
    {
        $this->transition($session, ['requested'], ['state' => 'cancelled', 'ended_at' => now()]);
    }

    public function leave(LiveSession $session): LiveSession
    {
        $ended = $this->transition($session, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'], ['state' => 'ended', 'end_reason' => 'participant_left', 'ended_at' => now()]);
        app(SessionReportService::class)->ensureForEndedSession($ended);
        event(new SessionEnded($ended));

        return $ended;
    }

    public function end(LiveSession $session, ?string $reason = null): LiveSession
    {
        $ended = $this->transition($session, ['accepted', 'connecting', 'direct_negotiation', 'connected', 'weak_connection', 'reconnecting', 'disconnected'], ['state' => 'ended', 'end_reason' => $reason, 'ended_at' => now()]);
        app(SessionReportService::class)->ensureForEndedSession($ended);
        event(new SessionEnded($ended));

        return $ended;
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
