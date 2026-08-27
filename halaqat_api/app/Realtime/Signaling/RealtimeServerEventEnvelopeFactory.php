<?php

namespace App\Realtime\Signaling;

use App\Exceptions\RealtimeProtocolException;
use App\Models\RealtimeOutboxMessage;
use App\Realtime\RealtimeEventTypes;

class RealtimeServerEventEnvelopeFactory
{
    /** @return array<string,mixed> */
    public function make(RealtimeOutboxMessage $message): array
    {
        if (! in_array($message->event_type, RealtimeEventTypes::all(), true)) {
            throw new RealtimeProtocolException('unsupported_server_event_type', 'The server realtime event type is not supported.');
        }

        $session = $message->relationLoaded('session') ? $message->session : $message->session()->firstOrFail();
        $participants = [(string) $session->teacher_id, (string) $session->student_id];
        if (! in_array((string) $message->recipient_id, $participants, true)) {
            throw new RealtimeProtocolException('server_event_recipient_mismatch', 'A server realtime event must target a session participant.');
        }

        return [
            'source' => 'server',
            'message_id' => (string) $message->id,
            'session_id' => (string) $message->session_id,
            'sender_id' => null,
            'recipient_id' => (string) $message->recipient_id,
            'sender_role' => 'server',
            'type' => (string) $message->event_type,
            'occurred_at' => ($message->created_at ?? now())->toISOString(),
            'client_operation_id' => null,
            'payload' => $message->payload,
        ];
    }
}
