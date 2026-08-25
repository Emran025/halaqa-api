<?php

namespace App\Services\LiveSessions;

use App\Models\LiveSession;
use App\Models\RealtimeOutboxMessage;
use App\Models\SessionReport;
use App\Realtime\RealtimeEventTypes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RealtimeOutboxPublisher
{
    public function publishSessionState(LiveSession $session, string $eventType): void
    {
        $payload = ['state' => (string) $session->state];
        if ($session->end_reason !== null) {
            $payload['reason'] = (string) $session->end_reason;
        }

        $this->publish($session, $eventType, $payload);
    }

    public function publishReportUpdated(SessionReport $report): void
    {
        $session = $report->relationLoaded('session') ? $report->session : $report->session()->firstOrFail();
        $this->publish($session, RealtimeEventTypes::REPORT_UPDATED, [
            'report_id' => (string) $report->id,
            'state' => (string) $report->state,
            'version' => (int) $report->version,
        ]);
    }

    /** @param array<string,mixed> $payload */
    public function publish(LiveSession $session, string $eventType, array $payload): void
    {
        if (! in_array($eventType, RealtimeEventTypes::all(), true)) {
            throw new InvalidArgumentException('The realtime event type is not supported.');
        }
        $this->validatePayload($eventType, $payload);

        DB::transaction(function () use ($session, $eventType, $payload): void {
            foreach ($this->recipientsFor($session, $eventType) as $recipientId) {
                $dedupeKey = hash('sha256', implode('|', [(string) $session->id, $recipientId, $eventType, json_encode($payload, JSON_THROW_ON_ERROR)]));
                RealtimeOutboxMessage::firstOrCreate(
                    ['dedupe_key' => $dedupeKey],
                    [
                        'id' => (string) Str::uuid(),
                        'session_id' => (string) $session->id,
                        'recipient_id' => $recipientId,
                        'event_type' => $eventType,
                        'payload' => $payload,
                    ],
                );
            }
        });
    }

    /** @return list<string> */
    private function recipientsFor(LiveSession $session, string $eventType): array
    {
        return match ($eventType) {
            RealtimeEventTypes::SESSION_REQUESTED => [(string) $session->student_id],
            RealtimeEventTypes::SESSION_ACCEPTED, RealtimeEventTypes::SESSION_REJECTED => [(string) $session->teacher_id],
            default => array_unique([(string) $session->teacher_id, (string) $session->student_id]),
        };
    }

    /** @param array<string,mixed> $payload */
    private function validatePayload(string $eventType, array $payload): void
    {
        if (in_array($eventType, [RealtimeEventTypes::SESSION_REQUESTED, RealtimeEventTypes::SESSION_ACCEPTED, RealtimeEventTypes::SESSION_REJECTED, RealtimeEventTypes::SESSION_STATE_CHANGED, RealtimeEventTypes::SESSION_ENDED], true)) {
            if (! isset($payload['state']) || ! is_string($payload['state']) || trim($payload['state']) === '') {
                throw new InvalidArgumentException('Session realtime events require a state payload.');
            }
            if (array_key_exists('reason', $payload) && (! is_string($payload['reason']) || strlen($payload['reason']) > 500)) {
                throw new InvalidArgumentException('Session realtime event reasons must be strings of 500 characters or fewer.');
            }

            return;
        }

        if ($eventType === RealtimeEventTypes::DIRECT_CONNECTION_UNAVAILABLE) {
            if (($payload['state'] ?? null) !== 'direct_connection_unavailable' || ! is_string($payload['reason'] ?? null) || trim($payload['reason']) === '' || strlen($payload['reason']) > 500) {
                throw new InvalidArgumentException('The direct connection unavailable payload is invalid.');
            }

            return;
        }

        if ($eventType === RealtimeEventTypes::REPORT_UPDATED && (! Str::isUuid((string) ($payload['report_id'] ?? '')) || ! is_string($payload['state'] ?? null) || ! in_array($payload['state'], ['draft', 'pending_student_acknowledgment', 'completed', 'reopened'], true) || ! is_int($payload['version']) || $payload['version'] < 1)) {
            throw new InvalidArgumentException('The report realtime payload is invalid.');
        }
    }
}
